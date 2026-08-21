<?php

namespace App\Services\Lembretes;

use App\Core\DB;
use App\Services\NotificacaoService;

/**
 * Agenda e processa os lembretes de um evento de agenda — interno (técnico, sempre in-app via
 * NotificacaoService) e opcional do cliente (WhatsApp/e-mail, via NotificacaoProviderInterface).
 *
 * `agenda_lembretes_fila` é fila E log: cada linha é um disparo agendado que vira o próprio
 * registro do envio quando processada. reagendar() é chamado pelo AgendaController sempre que
 * um evento é criado/editado (ver salvar()); cancelarPendentes() quando é excluído ou cancelado.
 * processarFila() é o "worker" — chamado por um poller throttled (NotificacaoController) e por
 * scripts/processar_lembretes_agenda.php pra quem tiver cron de verdade. Ver CLAUDE.md
 * ("Lembretes de agenda") pro desenho completo.
 *
 * Séries recorrentes não têm suas ocorrências materializadas em `agenda` (ver rrule.php) — os
 * lembretes seguem o mesmo princípio: só agenda disparos pra uma JANELA de dias à frente
 * (self::JANELA_SERIE_DIAS), nunca a série inteira. reagendar() re-topa essa janela a cada
 * chamada; rodar o poller pelo menos 1x/dia é o suficiente pra manter a janela sempre cheia.
 */
class AgendaLembreteService
{
    private const JANELA_SERIE_DIAS = 35;

    private NotificacaoProviderInterface $provider;

    public function __construct(?NotificacaoProviderInterface $provider = null)
    {
        $this->provider = $provider ?? NotificacaoProviderFactory::criar();
    }

    public static function mensagemPadrao(): string
    {
        return 'Olá {{cliente}}! Passando para lembrar do seu atendimento agendado para {{data}} às {{hora}}. Endereço: {{endereco}}';
    }

    /**
     * Re-agenda todos os lembretes de uma linha de `agenda` (evento normal, exceção
     * materializada, ou mestre de série) a partir da config atual dela — chamar depois de
     * qualquer criação/edição. Idempotente: cancela os pendentes antigos antes de recriar.
     */
    public function reagendar(int $agendaId, int $eid): void
    {
        $row = $this->buscarContexto($agendaId, $eid);
        if (!$row) return;

        if (!empty($row['rrule'])) {
            $this->reagendarSerie($row, $eid);
            return;
        }

        $this->cancelarPendentes($agendaId, $eid, null);
        if ($row['status'] === 'cancelado' || !empty($row['recorrencia_excluida'])) return;
        $this->agendarOcorrencia($row, $eid, $agendaId, null, $row['data_inicio']);
    }

    /** Cancela lembretes pendentes de um evento (ou, se $ocorrenciaData for informado, só de
     *  uma ocorrência daquela série) — chamar ao excluir ou marcar como cancelado. Não mexe em
     *  linhas já enviadas/com falha: é histórico, fica registrado. */
    public function cancelarPendentes(int $agendaId, int $eid, ?string $ocorrenciaData = null): void
    {
        $db = DB::pdo();
        if ($ocorrenciaData === null) {
            $db->prepare(
                "UPDATE agenda_lembretes_fila SET status = 'cancelado'
                 WHERE agenda_id = ? AND empresa_id = ? AND status = 'pendente'"
            )->execute([$agendaId, $eid]);
        } else {
            $db->prepare(
                "UPDATE agenda_lembretes_fila SET status = 'cancelado'
                 WHERE agenda_id = ? AND empresa_id = ? AND ocorrencia_data = ? AND status = 'pendente'"
            )->execute([$agendaId, $eid, $ocorrenciaData]);
        }
    }

    /** Processa até $lote disparos vencidos (status=pendente, disparar_em <= agora). Chamado
     *  pelo poller throttled e pelo script de cron. Retorna um resumo pra log/depuração. */
    public function processarFila(int $lote = 50): array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT * FROM agenda_lembretes_fila WHERE status = 'pendente' AND disparar_em <= NOW()
             ORDER BY disparar_em LIMIT ?"
        );
        $stmt->bindValue(1, $lote, \PDO::PARAM_INT);
        $stmt->execute();

        $resultado = ['processados' => 0, 'enviados' => 0, 'falhas' => 0, 'cancelados' => 0];
        foreach ($stmt->fetchAll() as $fila) {
            $resultado['processados']++;
            $resultado[$this->processarUm($fila)]++;
        }
        return $resultado;
    }

    /** Throttle simples (arquivo marcador em /tmp) pra não reprocessar a fila a cada request —
     *  mesmo padrão de NotificacaoController::gerarThrottled(), só que global (a fila não é por
     *  empresa) e com intervalo menor (lembrete é sensível a horário, notificação não). */
    public static function processarFilaThrottled(): void
    {
        $marcador = sys_get_temp_dir() . '/fixaos_lembretes_fila';
        if (is_file($marcador) && (time() - filemtime($marcador)) <= 60) return;
        @touch($marcador);
        (new self())->processarFila();
        self::enviarAlertasPendentes();
    }

    private const INTERVALO_ALERTA_PENDENTE_HORAS = 3;

    /**
     * "Alerta de pendência" — evento com técnico vinculado cujo horário já passou e ninguém
     * marcou como concluído/cancelado (ver CLAUDE.md "Alerta de evento não concluído"). Re-notifica
     * a cada `INTERVALO_ALERTA_PENDENTE_HORAS`, direto na tabela `agenda` (não usa
     * `agenda_lembretes_fila`, que é pra disparo único por offset fixo — aqui é um alerta
     * repetido até alguém resolver, sem número final de tentativas).
     *
     * Só olha eventos concretos (`rrule IS NULL`) — cobre evento normal e exceção já
     * materializada de série (cada uma já é uma linha própria, com `status`/`data_inicio`
     * reais). O mestre de uma série recorrente (`rrule` preenchido) nunca é alertado aqui: seu
     * `data_inicio` é só a âncora original da regra, não representa "hoje" de verdade — mesmo
     * princípio de não confiar em ocorrência não materializada usado no resto da Agenda.
     */
    public static function enviarAlertasPendentes(): array
    {
        $db = DB::pdo();
        $stmt = $db->query(
            "SELECT id, empresa_id, usuario_id, titulo, data_inicio FROM agenda
             WHERE rrule IS NULL
               AND status NOT IN ('concluido', 'cancelado')
               AND usuario_id IS NOT NULL
               AND (recorrencia_excluida = 0 OR recorrencia_excluida IS NULL)
               AND data_inicio <= NOW()
               AND (ultimo_alerta_pendente_em IS NULL
                    OR ultimo_alerta_pendente_em <= NOW() - INTERVAL " . self::INTERVALO_ALERTA_PENDENTE_HORAS . " HOUR)"
        );

        // Insere direto (não via NotificacaoService::criar()) — o dedup padrão dele é "mesmo
        // empresa+tipo+link nas últimas 6h", pensado pra evitar duplicata ACIDENTAL de
        // caminhos de disparo independentes. Aqui o link é sempre o mesmo pro mesmo evento (é
        // assim que se quer, pra sempre abrir o evento certo), e o reenvio a cada 3h É a
        // intenção — usar aquele dedup engoliria silenciosamente metade dos reenvios (3h < 6h).
        // `ultimo_alerta_pendente_em` já É o dedup certo pra este caso.
        $stmtIns = $db->prepare(
            "INSERT INTO notificacoes (empresa_id, usuario_id, tipo, titulo, mensagem, link, icone, cor)
             VALUES (?, ?, 'agenda_pendente_confirmacao', ?, ?, ?, 'bi-exclamation-octagon-fill', 'danger')"
        );

        $enviados = 0;
        foreach ($stmt->fetchAll() as $ev) {
            $stmtIns->execute([
                (int) $ev['empresa_id'],
                (int) $ev['usuario_id'],
                'Ainda não concluído: ' . $ev['titulo'],
                'Esse evento já passou do horário (' . date('d/m \à\s H:i', strtotime($ev['data_inicio'])) . ') e continua sem confirmação.',
                url('/agenda?data=' . substr($ev['data_inicio'], 0, 10) . '&evento=' . $ev['id']),
            ]);
            $db->prepare("UPDATE agenda SET ultimo_alerta_pendente_em = NOW() WHERE id = ?")
               ->execute([$ev['id']]);
            $enviados++;
        }
        return ['enviados' => $enviados];
    }

    private function processarUm(array $fila): string
    {
        $eid = (int) $fila['empresa_id'];
        $agendaId = (int) $fila['agenda_id'];

        $efetivo = $this->resolverEventoEfetivo($agendaId, $eid, $fila['ocorrencia_data']);
        if ($efetivo === null || $efetivo['status'] === 'cancelado' || !empty($efetivo['recorrencia_excluida'])) {
            $this->marcarCancelado((int) $fila['id']);
            return 'cancelados';
        }

        try {
            if ($fila['destinatario_tipo'] === 'tecnico') {
                if (empty($efetivo['usuario_id'])) throw new NotificacaoEnvioException('Sem técnico responsável.');
                // tipo "lembrete_agenda_som" (em vez de "lembrete_agenda") é o sinal que o
                // poller de notificações (carregarNotifs() em layouts/main.php) usa pra saber
                // que esse lembrete específico deve tocar um beep — ver coluna
                // agenda_lembretes_fila.som e o toggle "Alerta sonoro" no modal de evento.
                NotificacaoService::criar(
                    $eid,
                    !empty($fila['som']) ? 'lembrete_agenda_som' : 'lembrete_agenda',
                    'Lembrete: ' . $efetivo['titulo'],
                    (string) $fila['mensagem'],
                    // inclui o id (não só a data) no link — evita que o dedup de 6h de
                    // NotificacaoService::criar() (empresa+tipo+link) engula o lembrete de um
                    // segundo evento no mesmo dia por terem o mesmo link.
                    url('/agenda?data=' . substr($efetivo['data_inicio'], 0, 10) . '&evento=' . $agendaId),
                    !empty($fila['som']) ? 'bi-volume-up-fill' : 'bi-alarm',
                    'primary',
                    (int) $efetivo['usuario_id']
                );
            } else {
                $this->provider->enviar($eid, $fila['canal'], (string) $fila['destino'], (string) $fila['mensagem']);
            }
            $this->registrarSucesso((int) $fila['id']);
            return 'enviados';
        } catch (\Throwable $e) {
            $this->registrarFalha($fila, $e->getMessage());
            return 'falhas';
        }
    }

    /** Resolve a linha de `agenda` que efetivamente manda no disparo: pra ocorrência virtual
     *  (ocorrencia_data preenchido) checa se virou uma exceção materializada nesse meio-tempo —
     *  se sim, é ELA quem decide (pode ter sido editada/cancelada); senão, usa o mestre. */
    private function resolverEventoEfetivo(int $agendaId, int $eid, ?string $ocorrenciaData): ?array
    {
        if ($ocorrenciaData === null) {
            return $this->buscarContexto($agendaId, $eid);
        }

        $stmt = DB::pdo()->prepare(
            "SELECT id FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        );
        $stmt->execute([$eid, $agendaId, $ocorrenciaData]);
        $excecaoId = $stmt->fetchColumn();

        return $this->buscarContexto($excecaoId ? (int) $excecaoId : $agendaId, $eid);
    }

    private function reagendarSerie(array $master, int $eid): void
    {
        $masterId = (int) $master['id'];

        DB::pdo()->prepare(
            "UPDATE agenda_lembretes_fila SET status = 'cancelado'
             WHERE agenda_id = ? AND empresa_id = ? AND ocorrencia_data IS NOT NULL AND status = 'pendente'"
        )->execute([$masterId, $eid]);

        $de  = date('Y-m-d');
        $ate = date('Y-m-d', strtotime('+' . self::JANELA_SERIE_DIAS . ' days'));
        $ocorrencias = agenda_rrule_expandir($master['data_inicio'], $master['rrule'], $de, $ate);
        if (!$ocorrencias) return;

        $stmtExc = DB::pdo()->prepare(
            "SELECT recorrencia_data_original FROM agenda WHERE empresa_id = ? AND recorrencia_id = ?"
        );
        $stmtExc->execute([$eid, $masterId]);
        $comExcecao = array_flip(array_column($stmtExc->fetchAll(), 'recorrencia_data_original'));

        foreach ($ocorrencias as $dtOcorrencia) {
            $dataChave = substr($dtOcorrencia, 0, 10);
            // Ocorrência com exceção própria (editada ou excluída) tem sua própria linha em
            // `agenda` — o reagendar() DELA (chamado separadamente ao salvar/excluir) cuida do
            // lembrete; não duplica aqui.
            if (isset($comExcecao[$dataChave])) continue;
            $this->agendarOcorrencia($master, $eid, $masterId, $dataChave, $dtOcorrencia);
        }
    }

    private function agendarOcorrencia(array $row, int $eid, int $agendaId, ?string $ocorrenciaData, string $dataInicioEfetiva): void
    {
        $tsInicio = strtotime($dataInicioEfetiva);
        if (!$tsInicio) return;
        $agora = time();

        if (!empty($row['usuario_id']) && (!empty($row['lembrete_tecnico_offsets']) || !empty($row['alerta_sonoro']))) {
            $mensagemTecnico = $row['titulo'] . (!empty($row['cliente_nome']) ? ' — ' . $row['cliente_nome'] : '')
                . ' às ' . date('H:i', $tsInicio);

            // Offsets dos checkboxes de "Lembrete interno" + offset 0 ("na hora") se o alerta
            // sonoro estiver ligado — mesmo sem "Na hora" marcado nos checkboxes, o alerta
            // sonoro é um gatilho independente, sempre no vencimento (offset 0).
            $offsets = [];
            if (!empty($row['lembrete_tecnico_offsets'])) {
                foreach (explode(',', $row['lembrete_tecnico_offsets']) as $offsetStr) {
                    $offsets[(int) trim($offsetStr)] = true;
                }
            }
            if (!empty($row['alerta_sonoro'])) $offsets[0] = true;

            foreach (array_keys($offsets) as $offset) {
                if ($offset < 0 || ($tsInicio - $offset * 60) <= $agora) continue;
                $som = ($offset === 0 && !empty($row['alerta_sonoro'])) ? 1 : 0;
                $this->inserirDisparo($eid, $agendaId, $ocorrenciaData, 'tecnico', 'interno', $offset, $tsInicio, 'interno', $mensagemTecnico, $som);
            }
        }

        if (!empty($row['lembrete_cliente_ativo']) && !empty($row['lembrete_cliente_canal'])) {
            $offset = (int) ($row['lembrete_cliente_offset'] ?? 60);
            $canal  = (string) $row['lembrete_cliente_canal'];
            $destino = $canal === 'email' ? (string) ($row['cliente_email'] ?? '') : (string) ($row['cliente_whatsapp'] ?? '');

            if ($destino !== '' && ($tsInicio - $offset * 60) > $agora) {
                $mensagem = $this->renderizarMensagem(
                    (string) ($row['lembrete_cliente_mensagem'] ?: self::mensagemPadrao()),
                    [
                        'cliente'  => $row['cliente_nome'] ?? '',
                        'data'     => date('d/m/Y', $tsInicio),
                        'hora'     => date('H:i', $tsInicio),
                        'os'       => !empty($row['os_numero']) ? ('#' . $row['os_numero']) : '',
                        'endereco' => $row['cliente_endereco'] ?? '',
                    ]
                );
                $this->inserirDisparo($eid, $agendaId, $ocorrenciaData, 'cliente', $canal, $offset, $tsInicio, $destino, $mensagem);
            }
        }
    }

    private function inserirDisparo(int $eid, int $agendaId, ?string $ocorrenciaData, string $destinatario, string $canal, int $offset, int $tsInicio, string $destino, string $mensagem, int $som = 0): void
    {
        $disparo = date('Y-m-d H:i:s', $tsInicio - $offset * 60);
        DB::pdo()->prepare(
            "INSERT INTO agenda_lembretes_fila
                (empresa_id, agenda_id, ocorrencia_data, destinatario_tipo, canal, offset_minutos, som, disparar_em, destino, mensagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                disparar_em = VALUES(disparar_em), destino = VALUES(destino), mensagem = VALUES(mensagem), som = VALUES(som),
                status = IF(status = 'pendente', 'pendente', status)"
        )->execute([$eid, $agendaId, $ocorrenciaData, $destinatario, $canal, $offset, $som, $disparo, $destino, $mensagem]);
    }

    private function renderizarMensagem(string $template, array $vars): string
    {
        return strtr($template, [
            '{{cliente}}'  => $vars['cliente']  ?? '',
            '{{data}}'     => $vars['data']     ?? '',
            '{{hora}}'     => $vars['hora']     ?? '',
            '{{os}}'       => $vars['os']       ?? '',
            '{{endereco}}' => $vars['endereco'] ?? '',
        ]);
    }

    private function registrarSucesso(int $filaId): void
    {
        DB::pdo()->prepare("UPDATE agenda_lembretes_fila SET status = 'enviado', enviado_em = NOW() WHERE id = ?")
                  ->execute([$filaId]);
    }

    private function marcarCancelado(int $filaId): void
    {
        DB::pdo()->prepare("UPDATE agenda_lembretes_fila SET status = 'cancelado' WHERE id = ?")
                  ->execute([$filaId]);
    }

    /** Registra a falha; reagenda com backoff simples (15min * tentativa) até max_tentativas,
     *  depois marca 'falha' definitivo (fica no log, não tenta mais). */
    private function registrarFalha(array $fila, string $erro): void
    {
        $tentativas = (int) $fila['tentativas'] + 1;
        $db = DB::pdo();

        if ($tentativas >= (int) $fila['max_tentativas']) {
            $db->prepare("UPDATE agenda_lembretes_fila SET status = 'falha', tentativas = ?, ultimo_erro = ? WHERE id = ?")
               ->execute([$tentativas, $erro, $fila['id']]);
            return;
        }

        $proxima = date('Y-m-d H:i:s', time() + 900 * $tentativas);
        $db->prepare("UPDATE agenda_lembretes_fila SET tentativas = ?, ultimo_erro = ?, disparar_em = ? WHERE id = ?")
           ->execute([$tentativas, $erro, $proxima, $fila['id']]);
    }

    private function buscarContexto(int $agendaId, int $eid): ?array
    {
        $stmt = DB::pdo()->prepare(
            "SELECT a.*, c.nome AS cliente_nome, c.email AS cliente_email,
                    COALESCE(NULLIF(c.whatsapp, ''), c.telefone) AS cliente_whatsapp,
                    CONCAT_WS(', ', NULLIF(CONCAT_WS(', ', c.logradouro, c.numero), ''), c.bairro, c.cidade, c.uf) AS cliente_endereco,
                    os.numero AS os_numero
             FROM agenda a
             LEFT JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN ordens_servico os ON os.id = a.os_id
             WHERE a.id = ? AND a.empresa_id = ?"
        );
        $stmt->execute([$agendaId, $eid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

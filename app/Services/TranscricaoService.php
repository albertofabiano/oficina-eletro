<?php

namespace App\Services;

/**
 * Transcrição de áudio — fallback do Lançamento por voz do Carteira Fixa
 * (FinanceiroPessoalController::vozTranscrever()) pra quando a Web Speech API do navegador
 * falha ou não existe (ex.: Safari do iPhone). Caminho PRINCIPAL continua sendo a Web Speech
 * API, direto no navegador, sem gastar nada daqui — este serviço só é chamado quando o
 * front-end precisa gravar o áudio e mandar pro servidor.
 *
 * Provedor: OpenAI Whisper (`whisper-1`), escolhido explicitamente pelo usuário — chave própria
 * (`openai_api_key` em `sistema_config`, mesmo padrão de `ia_api_key`/`imei_api_key`), nunca a
 * mesma chave da Anthropic. Configurado em Master → IA (app/Views/master/ia.php).
 */
class TranscricaoService
{
    // Preço público da OpenAI pro Whisper (whisper-1): USD 0,006 por minuto de áudio,
    // arredondado pra cima pro minuto cheio (é como a OpenAI cobra). Não é por token — por
    // isso não entra em config/ia_precos.php (que é só preço de modelo Anthropic, ver
    // IAUsoService::custoCentavos()) — registrado via IAUsoService::registrarCustoFixo().
    private const USD_POR_MINUTO = 0.006;

    public static function apiKey(): string
    {
        return (string) \App\Services\IAService::cfg('openai_api_key', '');
    }

    public static function disponivel(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * Transcreve um áudio (webm/ogg/mp4, como o MediaRecorder do navegador grava) pra texto em
     * português. Nunca guarda o áudio depois — quem chama é responsável por apagar o arquivo
     * temporário assim que esta função retornar (ver vozTranscrever(), `@unlink` logo após).
     */
    public static function transcrever(string $caminhoAudio, string $mimeType, int $usuarioId = 0, int $empresaId = 0): ?string
    {
        $key = self::apiKey();
        if ($key === '' || !is_file($caminhoAudio)) return null;

        $ext = match (true) {
            str_contains($mimeType, 'webm') => 'webm',
            str_contains($mimeType, 'ogg')  => 'ogg',
            str_contains($mimeType, 'mp4')  => 'mp4',
            str_contains($mimeType, 'wav')  => 'wav',
            default                         => 'webm',
        };

        $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
            CURLOPT_POSTFIELDS     => [
                'file'            => new \CURLFile($caminhoAudio, $mimeType, 'audio.' . $ext),
                'model'           => 'whisper-1',
                'language'        => 'pt',
                'response_format' => 'json',
            ],
            CURLOPT_TIMEOUT => 25,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::registrarCusto($caminhoAudio, $usuarioId, $empresaId);

        if ($res === false || $code < 200 || $code >= 300) return null;
        $j = json_decode((string) $res, true);
        $texto = trim((string) ($j['text'] ?? ''));
        return $texto !== '' ? $texto : null;
    }

    /** Custo estimado a partir da DURAÇÃO do áudio (não dá pra saber os segundos exatos sem
     *  decodificar o container de verdade — estima pelo tamanho do arquivo a ~16kbps, faixa
     *  típica de voz comprimida no navegador, suficiente pra uma estimativa de custo, não um
     *  valor cobrado de verdade). */
    private static function registrarCusto(string $caminhoAudio, int $usuarioId, int $empresaId): void
    {
        $bytes = @filesize($caminhoAudio) ?: 0;
        $minutos = max(1, (int) ceil(($bytes * 8 / 16000) / 60));
        $custoCentavos = round($minutos * self::USD_POR_MINUTO * (float) (IAUsoService::precos()['dolar_brl'] ?? 5.5) * 100, 4);
        IAUsoService::registrarCustoFixo($usuarioId ?: null, $empresaId ?: null, 'whisper-1', 'fixa_voz_transcricao', $custoCentavos);
    }
}

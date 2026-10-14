<div class="topo">
  <h1>Produtos</h1>
  <a href="/admin/produtos/novo" class="btn">+ Novo produto</a>
</div>

<div class="card">
  <?php if (empty($produtos)): ?>
    <p style="color:var(--texto-sec);margin:0">Nenhum produto cadastrado ainda.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Produto</th><th>Loja</th><th>Categoria</th><th>Preço</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($produtos as $p): ?>
      <tr>
        <td>
          <a href="/produto/<?= e($p['slug']) ?>" target="_blank"><?= e($p['nome']) ?></a>
          <?php if ($p['destaque_home']): ?><span class="badge badge-verde" style="margin-left:6px">Destaque</span><?php endif; ?>
        </td>
        <td><span class="badge <?= ($p['loja'] ?? 'mercado_livre') === 'aliexpress' ? 'badge-vermelho' : 'badge-amarelo' ?>"><?= ($p['loja'] ?? 'mercado_livre') === 'aliexpress' ? 'AliExpress' : 'Mercado Livre' ?></span></td>
        <td><?= e($p['categoria_nome'] ?? '—') ?></td>
        <td><?= $p['preco'] !== null ? money((float) $p['preco']) : '—' ?></td>
        <td><span class="badge <?= $p['ativo'] ? 'badge-verde' : 'badge-cinza' ?>"><?= $p['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
        <td style="white-space:nowrap">
          <a href="/admin/produtos/<?= (int) $p['id'] ?>/editar" class="btn btn-secundario btn-sm">Editar</a>
          <form method="POST" action="/admin/produtos/<?= (int) $p['id'] ?>/excluir" style="display:inline" onsubmit="return confirm('Excluir este produto definitivamente?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-perigo btn-sm">Excluir</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

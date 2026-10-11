<div class="topo">
  <h1>Guias</h1>
  <a href="/admin/guias/novo" class="btn">+ Novo guia</a>
</div>

<div class="card">
  <?php if (empty($guias)): ?>
    <p style="color:var(--texto-sec);margin:0">Nenhum guia cadastrado ainda.</p>
  <?php else: ?>
  <table>
    <thead><tr><th>Título</th><th>Status</th><th>Atualizado em</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($guias as $g): ?>
      <tr>
        <td>
          <?php if ($g['status'] === 'publicado'): ?>
            <a href="/guias/<?= e($g['slug']) ?>" target="_blank"><?= e($g['titulo']) ?></a>
          <?php else: ?>
            <?= e($g['titulo']) ?>
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $g['status'] === 'publicado' ? 'badge-verde' : 'badge-cinza' ?>"><?= $g['status'] === 'publicado' ? 'Publicado' : 'Rascunho' ?></span></td>
        <td><?= e(date('d/m/Y H:i', strtotime($g['atualizado_em']))) ?></td>
        <td style="white-space:nowrap">
          <a href="/admin/guias/<?= (int) $g['id'] ?>/editar" class="btn btn-secundario btn-sm">Editar</a>
          <form method="POST" action="/admin/guias/<?= (int) $g['id'] ?>/excluir" style="display:inline" onsubmit="return confirm('Excluir este guia definitivamente?')">
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

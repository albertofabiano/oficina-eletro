<?php
/**
 * Script de instalação do banco de dados
 * Executar via: php database/install.php
 * Ou acessar via browser: http://localhost/oficina-eletro/database/install.php
 */

define('BASE_PATH', dirname(__DIR__));
$cfg = require BASE_PATH . '/config/database.php';

echo "<!DOCTYPE html><html><head><meta charset=UTF-8><title>Instalação</title>";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>";
echo "</head><body class='bg-light'><div class='container py-4'>";
echo "<h2>🔌 FixaOS — Instalação</h2><hr>";

try {
    // Criar banco se não existir
    $dsn = "{$cfg['driver']}:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='text-success'>✅ Banco de dados <strong>{$cfg['database']}</strong> criado/verificado.</p>";

    // Conectar ao banco
    $dsn2 = "{$cfg['driver']}:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
    $pdo2 = new PDO($dsn2, $cfg['username'], $cfg['password'], $cfg['options']);

    // Executar schema
    $schema = file_get_contents(BASE_PATH . '/database/migrations/001_schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $sql) {
        if ($sql) $pdo2->exec($sql);
    }
    echo "<p class='text-success'>✅ Tabelas criadas com sucesso.</p>";

    // Seeds
    $seeds = file_get_contents(BASE_PATH . '/database/seeds/001_dados_iniciais.sql');
    $seedStmts = array_filter(array_map('trim', explode(';', $seeds)));
    foreach ($seedStmts as $sql) {
        if ($sql) {
            try { $pdo2->exec($sql); } catch (\Exception $e) { /* ignora duplicatas */ }
        }
    }
    echo "<p class='text-success'>✅ Dados iniciais inseridos.</p>";

    echo "<div class='alert alert-success mt-3'>";
    echo "<h4>✅ Instalação concluída!</h4>";
    echo "<p>Acesse: <a href='http://localhost/oficina-eletro/public'>http://localhost/oficina-eletro/public</a></p>";
    echo "<p><strong>Login:</strong> admin@techfix.com | <strong>Senha:</strong> Admin@123</p>";
    echo "</div>";

} catch (\Exception $e) {
    echo "<div class='alert alert-danger'><h4>❌ Erro:</h4><pre>" . htmlspecialchars($e->getMessage()) . "</pre></div>";
    echo "<p>Verifique as configurações em <code>config/database.php</code></p>";
}

echo "</div></body></html>";

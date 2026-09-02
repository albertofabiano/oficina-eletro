# Dompdf vendorizado — como funciona e como atualizar

O FixaOS não usa Composer em lugar nenhum do app (ver `CLAUDE.md`) — `lib/dompdf/vendor/`
é código-fonte de terceiros **commitado direto no git**, não uma dependência gerenciada em
tempo de execução. O Composer só é usado, uma vez, como ferramenta de empacotamento pra
baixar o Dompdf e as bibliotecas que ele mesmo depende — o resultado vira arquivo versionado
normal, igual qualquer outro `.php` do projeto. Nada no deploy ou no runtime precisa de
Composer instalado.

## Por que isso existe

Antes desta mudança, `lib/dompdf/vendor/` não estava no git — só os metadados
(`AUTHORS.md`/`LICENSE.LGPL`/`README.md`/`VERSION`) tinham entrado no snapshot de produção
importado pro repositório. Funcionava em produção porque a pasta existe no VPS por fora do
git, mas um checkout novo (sandbox de dev, disaster recovery, ambiente de teste) não conseguia
gerar PDF nenhum. Ver `CLAUDE.md`, seção "Padrão de deploy deste projeto".

## Versão atual: 3.1.6

A versão anterior gravada aqui (3.1.5) tinha **6 CVEs conhecidos** (leitura de arquivo local via
SVG em data-URI, DoS por bitmap/BMP com dimensões forjadas, bypass de validação de chroot —
ver `GHSA-j8qw-6jw8-r297`, `GHSA-f5gf-2cj8-52g2`, `GHSA-8hg6-c449-896m`, `GHSA-cx96-42px-69fm`,
`GHSA-7x2p-4jvh-6384`, `GHSA-wvh6-f5jh-8gw4`), todos corrigidos a partir da 3.1.6. A vendorização
já subiu direto pra 3.1.6, sem reproduzir a versão vulnerável.

## Como atualizar no futuro

Rode isto numa pasta separada, fora do repositório do FixaOS (o Composer nunca deve rodar
dentro do projeto em si):

```bash
mkdir /tmp/dompdf-build && cd /tmp/dompdf-build
composer require "dompdf/dompdf:^3" --no-interaction --prefer-dist -o
composer audit   # confirme "No security vulnerability advisories found" antes de prosseguir
```

Depois:

1. Apague `lib/dompdf/vendor/` do projeto e copie o `vendor/` gerado no lugar.
2. Remova o que não é necessário em runtime (reduz de ~60MB pra ~14MB, e nested `.git` quebraria
   o `git add` tratando como submódulo):
   ```bash
   cd lib/dompdf/vendor
   find . -maxdepth 3 -name ".git" -type d -exec rm -rf {} +
   find . -maxdepth 3 -type d \( -iname "tests" -o -iname ".github" -o -iname "test" -o -iname "examples" -o -iname "docs" \) -exec rm -rf {} +
   find . -maxdepth 3 -type f \( -iname "phpunit.xml*" -o -iname ".php-cs-fixer*" -o -iname "phpstan*" -o -iname "*.yml" -o -iname "*.yaml" \) -delete
   ```
3. Atualize `lib/dompdf/VERSION` com a versão nova.
4. Teste de ponta a ponta antes de commitar:
   ```bash
   php -r '
   define("BASE_PATH", __DIR__);
   require "app/Services/PdfService.php";
   $pdf = App\Services\PdfService::fromHtml("<h1>teste</h1>");
   echo $pdf === null ? "FALHOU\n" : "OK - " . strlen($pdf) . " bytes\n";
   '
   ```
5. Commit normal — `lib/dompdf/vendor/` passa a ser só mais código versionado do projeto.

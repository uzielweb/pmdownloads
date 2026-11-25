# Ponto Mega Downloads (`pkg_pmdownloads`)

Repositório nativo de downloads, releases versionadas, verificação SHA-256 e **Joomla Update Server XML** para **Joomla! 5 e Joomla! 6**, desenvolvido por [Uziel Almeida Oliveira (Ponto Mega)](https://pontomega.com.br).

- **Demonstração ao vivo:** https://pontomega.com.br/pt/downloads
- **Versão em Inglês:** https://pontomega.com.br/en/downloads
- **Download oficial:** https://pontomega.com.br/pt/downloads/ponto-mega-downloads-joomla-6

## Extensões incluídas no pacote

| Extensão | Tipo | Função |
|---|---|---|
| `com_pmdownloads` | Componente | Catálogo MVC nativo Joomla! 6 com múltiplas releases/arquivos por projeto, cálculo automático de tamanho e hash `SHA-256`, streaming protegido de arquivos locais (`.htaccess` + `Path::check`), redirecionamento seguro para GitHub Releases, sincronização 1-clique com GitHub API no Admin, filtros facetados AJAX (`Ativos J5/J6` vs `Arquivo Histórico`), rotas SEF sem IDs, associações multilíngues (`pt-BR` / `en-GB`), Schema.org `SoftwareApplication` JSON-LD e gerador nativo de **Update Server XML** + **Changelog XML**. |
| `mod_pmdownloads` | Módulo Site | Vitrine de lançamentos com layouts `default`, `grid` e `compact`. |
| `plg_finder_pmdownloads` | Plugin Smart Search | Indexa projetos, versões, arquivos e changelogs com 6 taxonomias (`Type`, `Category`, `Joomla Version`, `Download Type`, `Maintenance Status`, `Language`). |
| `plg_content_pmdownloads` | Plugin Content | Renderiza cards dinâmicos de download em artigos via `{pmdownload alias="..."}` ou `{pmdownload id=1}`. |

## Endpoints nativos de Update Server XML

Para qualquer item com a opção **Gerar Update Server XML** ativada:

- Update Server: `/pt/downloads/<alias>?format=xml&layout=updateserver`
- Changelog XML: `/pt/downloads/<alias>?format=xml&layout=changelog`

## Requisitos

- Joomla! 5.x ou Joomla! 6.x
- PHP 8.1+ (testado até PHP 8.5)
- MySQL 8.0+ / 9.x ou MariaDB 10.6+ (`InnoDB`, `utf8mb4`)

## Instalação

1. Baixe `pkg_pmdownloads_v1.0.0.zip` na aba [Releases](https://github.com/uzielweb/pmdownloads/releases).
2. No administrador do Joomla!, acesse **Sistema > Instalar > Extensões** e envie o pacote `.zip`.
3. Configure o diretório protegido de armazenamento nas Opções do componente (padrão `files/pmdownloads`), ative os plugins Finder/Content e execute `php cli/joomla.php finder:index purge`.

## Empacotamento local a partir do código-fonte

```bash
python build.py
```

O pacote instalável é gerado em `dist/pkg_pmdownloads_v1.0.0.zip`.

## Licença

Distribuído sob licença **GPL-2.0-or-later**.

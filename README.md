# Ponto Mega Downloads (`pkg_pmdownloads`)

Native downloads repository, versioned releases catalog, SHA-256 integrity verifier, and GitHub-first Joomla! Update Server showcase for **Joomla! 5 and Joomla! 6**, developed by [Uziel Almeida Oliveira (Ponto Mega)](https://pontomega.com.br).

- **Live Demo (English):** https://pontomega.com.br/en/downloads
- **Live Demo (Portuguese):** https://pontomega.com.br/pt/downloads
- **Official Download Page:** https://pontomega.com.br/en/downloads/ponto-mega-downloads-joomla-6-en
- **Joomla Update Server XML:** https://raw.githubusercontent.com/uzielweb/pmdownloads/main/updates.xml
- **Changelog XML:** https://raw.githubusercontent.com/uzielweb/pmdownloads/main/changelog.xml

## Included Extensions

| Extension | Type | Purpose |
|---|---|---|
| `com_pmdownloads` | Component | Native Joomla! 6 DI/MVC catalog supporting multiple versioned releases per project, automatic file size and `SHA-256` calculation, protected local file streaming (`.htaccess` + `Path::check`), safe GitHub Releases redirects, one-click GitHub API release sync in the Admin panel, AJAX faceted filters (`Active J5/J6` vs `Legacy Archive`), ID-less SEF routing, multilingual associations (`en-GB` / `pt-BR`), Schema.org `SoftwareApplication` JSON-LD, and GitHub-first **Update Server XML** + **Changelog XML** links. |
| `mod_pmdownloads` | Site Module | Releases showcase module with `default`, `grid`, and `compact` layouts. |
| `plg_finder_pmdownloads` | Smart Search Plugin | Indexes projects, versions, files, and changelogs with 6 taxonomies (`Type`, `Category`, `Joomla Version`, `Download Type`, `Maintenance Status`, `Language`). |
| `plg_content_pmdownloads` | Content Plugin | Renders dynamic download cards inside Joomla! articles via `{pmdownload alias="..."}` or `{pmdownload id=1}`. |

## GitHub-First Update Server & Changelog Resolution

For every download item with Update Server enabled, `com_pmdownloads` resolves and advertises the canonical GitHub endpoints (`https://raw.githubusercontent.com/<owner>/<repo>/main/updates.xml` and `changelog.xml`, or custom explicit GitHub XML URLs configured per item). Legacy XML endpoint requests (`?format=xml&layout=updateserver` / `changelog`) automatically issue a `301` redirect to the configured GitHub raw XML when available, falling back to native XML generation only for non-GitHub self-hosted packages.

## Requirements

- Joomla! 5.x or Joomla! 6.x
- PHP 8.1+ (tested up to PHP 8.5)
- MySQL 8.0+ / 9.x or MariaDB 10.6+ (`InnoDB`, `utf8mb4`)

## Installation

1. Download `pkg_pmdownloads_v1.0.0.zip` from [GitHub Releases](https://github.com/uzielweb/pmdownloads/releases).
2. In your Joomla! Administrator panel, go to **System > Install > Extensions** and upload the `.zip` package.
3. Configure the protected local storage directory in Component Options (default `files/pmdownloads`), enable the Finder and Content plugins, and run `php cli/joomla.php finder:index purge`.

## Automatic Updates

This package ships with native Joomla! Updater integration hosted directly on GitHub:

- **Update Server:** `https://raw.githubusercontent.com/uzielweb/pmdownloads/main/updates.xml`
- **Changelog:** `https://raw.githubusercontent.com/uzielweb/pmdownloads/main/changelog.xml`

## Building Locally from Source

```bash
python build.py
```

The installable package is generated at `dist/pkg_pmdownloads_v1.0.0.zip`.

## License

Distributed under the **GPL-2.0-or-later** license. See [LICENSE](LICENSE) for details.

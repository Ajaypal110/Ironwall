=== Ironwall ===
Developer: Ajaypal Singh
Tags: security, firewall, waf, scanner, integrity, malware
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 4.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional-grade WordPress security monitoring, WAF, and Malware Scanner.

== Description ==

Ironwall is a lightweight yet powerful security suite designed to provide enterprise-level protection for WordPress sites. It features a proactive Web Application Firewall, a stateful AJAX-based Malware Scanner, and deep system hardening.

= Features =
* **Web Application Firewall (WAF)**: Block sophisticated SQLi, XSS, and LFI attacks in real-time.
* **AJAX Malware Scanner**: Incremental file analysis to find webshells and modified core files without timeouts.
* **Brute Force Protection**: Automatic IP banning for persistent failed login attempts.
* **Live Traffic Monitor**: Real-time visibility into every request hitting your server.
* **Login Stealth**: Obscure your login portal with custom slugs to hide from botnets.
* **Security Analytics**: Visual charts for monitoring event trends and traffic composition.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure settings via the 'Ironwall' dashboard.

== Changelog ==

= 4.6 =
* Integrated Chart.js for visual security analytics.
* Refactored Malware Scanner to use stateful AJAX batching (preventing timeouts).
* Removed 2FA subsystem for streamlined core security focus.
* Full i18n support and WordPress.org compliance hardening.

= 4.5 =
* Major architecture refactor (Autoloaded classes).
* Added uninstall.php for clean data removal.

# 🚀 .tinob Configuration & Package Ecosystem

Das **.tinob Ecosystem** ist eine modulare, deklarative Architektur zur ultraschnellen Entwicklung von Webapplikationen. Über eine zentrale `.tinob`-Konfigurationsdatei werden alle benötigten Packages (`tinob-*`) aktiviert, parametrisiert und dynamisch verdrahtet.

---

## 📖 Warum dieses Konzept? (Philosophie)

In traditionellen Frameworks ist Konfiguration oft über mehrere Dateien zerstreut (z. B. `.env`, YAML-, JSON- oder PHP-Dateien im `config/`-Ordner). Das `.tinob`-System löst dieses Problem durch eine einzige, zentrale Steuerungsdatei:

* **Trennung von Anwendungslogik & Konfiguration:** Packages enthalten nur den ausführbaren Code, aber keine hardgecodeten Parameter oder Texte.
* **Maximale Lesbarkeit:** Keine kognitive Belastung durch unnötige Syntaxzeichen (Klammern, Anführungszeichen, Semikolons).
* **Determinismus:** Die Reihenfolge in der Konfiguration bestimmt direkt das Systemverhalten beim Bootstrapping.

---

## 📄 Das Dateiformat: `.tinob`

Die `.tinob`-Datei nutzt eine einrückungsbasierte (indentation-based) Domain-Specific Language (DSL). Sie orientiert sich an der visuellen Klarheit von YAML oder Python.

### 📐 Parser- & Syntaktische Regeln im Detail

1. **Einrückung (Indentation):**
   * **Regel:** Es werden **exakt 4 Leerzeichen** pro Hierarchie-Ebene verwendet (keine Tabs).
   * **Erklärung:** Der Parser nutzt die Einrückungstiefe, um Eltern-Kind-Beziehungen im Datenbaum zu bestimmen.

2. **Sequential Bootstrapping (`config -> active`):**
   * **Regel:** Die unter `config -> active` gelisteten Packages werden **exakt von oben nach unten** in dieser Reihenfolge geladen und initialisiert.
   * **Erklärung:** Dadurch werden Abhängigkeiten sauber aufgelöst. Steht `tinob-languages` an erster Stelle, wird die Sprach-Engine registriert, bevor darauf folgende Pakete (wie `tinob-login-signin`) Texte anfordern.

3. **Feld-Deklarationen (`addFields` - DSL Syntax):**
   * **`string[LÄNGE]`:** Definiert einen String-Datentyp mit Längenbegrenzung.  
     * *Erklärung:* `string[30]` schränkt die Eingabe auf maximal 30 Zeichen ein und wird im Frontend als `maxlength="30"` gerendert.
   * **`#FORMAT` (Rautenzeichen):** Kennzeichnet eingebaute System-Formats & Validatoren.  
     * *Erklärung:* `#email` sagt dem System, dass es sich um eine E-Mail-Adresse handelt. Das Frontend rendert `<input type="email">`, das Backend verwendet eine RFC-konforme E-Mail-Validierung.
   * **`$REFERENZ` (Dollarzeichen):** Signalisiert eine direkte Feld-Verknüpfung / Abgleichs-Regel.  
     * *Erklärung:* `$password` bedeutet *"Dieses Feld muss den exakt gleichen Wert enthalten wie das Feld `password`"*. Ideal für Wiederholungsfelder wie `passwordRepeat`.

4. **Sprachanbindung via `tinob-languages`:**
   * **Regel:** Keine UI-Texte, Beschriftungen oder Fehlermeldungen stehen in der `.tinob`-Datei oder im Package-Code.
   * **Erklärung:** Jedes definierte Feld generiert automatisch einen Übersetzungsschlüssel nach dem Schema `[package-name].fields.[fieldname]`.  
     * *Beispiel:* Das Feld `passwordRepeat` im Package `tinob-login-signin` löst den Key `tinob-login-signin.fields.passwordRepeat` im Sprach-Package auf.

---

## 🛠️ Vollständiges Konfigurationsbeispiel

```tinob
config
    active
        tinob-languages
        tinob-weak-password-detector
        tinob-login-signin
        tinob-rbac
        tinob-syslog

packages
    tinob-languages
        defaultLocale: de_DE
        fallbackLocale: en_US
        path: vendor/tinob-languages/locales/

    tinob-weak-password-detector
        rules
            minLength: 8
            requireSpecialChars: true
            requireNumbers: true
            requireUppercase: true
            requireLowercase: true
        defaultBadPasswords
            path: vendor/tinob-weak-password-detector/defaultBadPasswords.php

    tinob-login-signin
        languagePath: vendor/tinob-login-signin/locales/
        addFields
            login
                name: string[11]
                email: #email
            signin
                name: string[11]
                email: #email
                password: string[30]
                passwordRepeat: $password

    tinob-rbac
        roles
            admin
                description: Administrator mit Vollzugriff
                inherits: editor
                permissions
                    - *
            editor
                description: Content Manager
                permissions
                    - posts.create
                    - posts.edit
                    - posts.delete
            user
                description: Standard-Benutzer
                permissions
                    - profile.edit

    tinob-syslog
        driver: file
        path: logs/system.log
        level: debug

```

---

## 📦 Core-Package-Ökosystem

| Package | Beschreibung | Hauptaufgabe & Erklärung |
| --- | --- | --- |
| **`tinob-languages`** | Central Localization | Lädt Wörterbücher und Übersetzungen. Stellt Textbausteine dynamisch für alle anderen Pakete zur Verfügung. Muss als erstes geladen werden. |
| **`tinob-weak-password-detector`** | Passwort-Sicherheitsprüfung | Prüft neue Passwörter gegen Komplexitätsregeln (Länge, Sonderzeichen, Zahlen) sowie Wortlisten bekannter schwacher Passwörter. |
| **`tinob-login-signin`** | Formular- & Auth-Generator | Baut Formulare (HTML/JSON) dynamisch basierend auf der `addFields`-Spezifikation auf und steuert den Registrierungs-/Login-Ablauf. |
| **`tinob-rbac`** | Dynamic Role & Access Control | Verwaltet Rollen, Vererbungen und Berechtigungsbäume. Prüft im Code, ob ein Benutzer Aktionen ausführen darf. |
| **`tinob-syslog`** | System- & Event-Logging | Nimmt Log-Einträge entgegen und schreibt sie in Dateien, Datenbanken oder Konsolen-Streams. |

---

## 📐 Detail-Spezifikation der Hauptkomponenten

### 1. Dynamic Field Generation (`tinob-login-signin`)

Wenn in der `.tinob`-Datei folgender Block definiert ist:

```tinob
signin
    email: #email
    password: string[30]
    passwordRepeat: $password

```

Liest der Formular-Generator diesen Block ein und baut intern drei vollwertige Formularfelder inklusive Validation & Translation auf:

* **`email`**:
* **Frontend-Rendering:** `<input type="email" name="email">`
* **Backend-Validierung:** `required` | `email_format`
* **Translation-Key:** `tinob-login-signin.fields.email`


* **`password`**:
* **Frontend-Rendering:** `<input type="password" name="password" maxlength="30">`
* **Backend-Validierung:** `required` | `max_length: 30` (wird bei der Registrierung zusätzlich an `tinob-weak-password-detector` zur Prüfung übergeben)
* **Translation-Key:** `tinob-login-signin.fields.password`


* **`passwordRepeat`**:
* **Frontend-Rendering:** `<input type="password" name="passwordRepeat">`
* **Backend-Validierung:** `required` | `same_as: password` (geprüft durch die `$`-Referenz)
* **Translation-Key:** `tinob-login-signin.fields.passwordRepeat`



---

### 2. Rollen & Rechte (`tinob-rbac`)

Das Paket baut beim Systemstart eine Rechte-Matrix auf und unterstützt Vererbung (`inherits`).

```tinob
roles
    admin
        inherits: editor
        permissions
            - *
    editor
        permissions
            - posts.create
            - posts.edit
            - posts.delete

```

* **Funktionsweise:** Der `admin` erbt automatisch alle Rechte des `editor`s (`posts.create`, `posts.edit`, `posts.delete`). Durch das Wildcard-Zeichen `*` erhält der Admin zusätzlich uneingeschränkten Vollzugriff auf alle Rechte im gesamten System.
* **Verwendung im Anwendungscode:**
```php
// Prüft, ob der angemeldete Benutzer die Berechtigung besitzt:
if ($user->can('posts.edit')) {
    // Zugriff wird für Benutzer mit Rolle 'editor' und 'admin' gewährt
}

```



---

## ⚙️ Lifecycle & Ablauf im System (Von Datei zu Code)

1. **Parsing (Einlesen):** Der `.tinob`-Parser liest die Konfigurationsdatei ein, analysiert die Einrückungen (4 Leerzeichen) und wandelt den Text in einen mehrdimensionalen Datenbaum (Array/Objekt) um.
2. **Sequential Bootstrapping (Laden):** Das System liest das Array `config.active` zeilenweise von oben nach unten durch. Jedes dort gelistete Paket wird nacheinander instanziiert.
3. **Dependency Injection (Konfigurations-Übergabe):** Bei der Initialisierung bekommt jedes Paket exakt seinen eigenen Unterbaum aus dem `packages`-Block übergeben.
4. **Execution (Ausführung):** Die Pakete sind startklar, miteinander verdrahtet und greifen bei Bedarf dynamisch auf den zentralen Übersetzungsservice `tinob-languages` zu.

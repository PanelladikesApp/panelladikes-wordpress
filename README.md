# Panelladikes Widgets

Πρόσθετο WordPress που εμφανίζει τα widget του [panelladikes.edu.gr](https://panelladikes.edu.gr) (υπολογισμός μορίων, βάσεις εισαγωγής, πρόγραμμα εξετάσεων, στοιχεία σχολής και Μηχανογραφικό) στις σελίδες του site σας, με ένα block του επεξεργαστή ή με shortcode.

[![Άδεια: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)

- [Χαρακτηριστικά](#χαρακτηριστικά)
- [Απαιτήσεις](#απαιτήσεις)
- [Εγκατάσταση](#εγκατάσταση)
- [Χρήση](#χρήση)
- [Παράμετροι](#παράμετροι)
- [Ενημερώσεις](#ενημερώσεις)
- [Ιδιωτικότητα και δεδομένα](#ιδιωτικότητα-και-δεδομένα)
- [Προσαρμογή εμφάνισης (White-Label)](#προσαρμογή-εμφάνισης-white-label)
- [Συχνά προβλήματα](#συχνά-προβλήματα)
- [Υποστήριξη](#υποστήριξη)
- [Ανάπτυξη](#ανάπτυξη)
- [Συνεισφορά και ασφάλεια](#συνεισφορά-και-ασφάλεια)
- [Άδεια](#άδεια)
- [English summary](#english-summary)

## Χαρακτηριστικά

- Πέντε widget: `calculator` (υπολογισμός μορίων), `baseis` (βάσεις εισαγωγής), `program` (πρόγραμμα εξετάσεων), `sxoli` (στοιχεία σχολής) και `mixanografiko` (Μηχανογραφικό), για ΓΕΛ και ΕΠΑΛ.
- Block «Panelladikes Widget» (κατηγορία Widgets) με ζωντανή προεπισκόπηση στον επεξεργαστή και αναζήτηση σχολής.
- Shortcode `[panelladikes …]` για κλασικό επεξεργαστή, widgets πλαισίου και page builders.
- Το plugin δεν έχει σελίδα ρυθμίσεων, δεν δημιουργεί options (κρατά μόνο ένα προσωρινό transient του updater, που σβήνεται κατά την απεγκατάσταση), δεν βάζει cookies και δεν κάνει analytics.
- Ελληνικό και αγγλικό περιβάλλον.
- Ενημερώσεις από τις εκδόσεις (Releases) του GitHub, με έλεγχο ακεραιότητας και υπογραφής πριν από την εγκατάσταση.

## Απαιτήσεις

| | Ελάχιστη έκδοση |
|---|---|
| WordPress | 6.5 |
| PHP | 8.2 |

## Εγκατάσταση

1. Κατεβάστε το `panelladikes-widgets.zip` από τον σταθερό σύνδεσμο:
   <https://github.com/PanelladikesApp/panelladikes-wordpress/releases/latest/download/panelladikes-widgets.zip>
2. Στον πίνακα διαχείρισης: **Πρόσθετα → Προσθήκη νέου → Μεταφόρτωση πρόσθετου**, επιλέξτε το αρχείο και πατήστε **Εγκατάσταση τώρα**.
3. Πατήστε **Ενεργοποίηση**.

> **Προσοχή:** μην κατεβάσετε το «Source code (zip)» που εμφανίζει αυτόματα το GitHub. Δεν είναι το πακέτο εγκατάστασης και δεν λειτουργεί ως πρόσθετο.

## Χρήση

### Block

1. Στον επεξεργαστή, προσθέστε το block **Panelladikes Widget** (κατηγορία Widgets).
2. Στο πλαϊνό πάνελ διαλέξτε τον τύπο του widget και το σύστημα (ΓΕΛ ή ΕΠΑΛ). Για το `sxoli` αναζητήστε τη σχολή με το όνομά της ή πληκτρολογήστε τον αριθμό της.
3. Ο επεξεργαστής δείχνει το πραγματικό widget. Οι ρυθμίσεις εμφάνισης βρίσκονται στο πάνελ **Appearance**.

### Shortcode

```text
[panelladikes type="calculator"]
[panelladikes type="baseis" edu="epal" theme="dark"]
[panelladikes type="sxoli" edu="gel" id="123"]
[panelladikes type="program" width="60%" align="center" shadow="md" radius="16"]
```

Το shortcode μπορεί να μπει σε οποιοδήποτε σημείο της σελίδας που εκτελεί shortcodes.

## Παράμετροι

Οι ίδιες παράμετροι ισχύουν για το block και για το shortcode. Μια άκυρη τιμή αγνοείται και χρησιμοποιείται η προεπιλογή.

| Παράμετρος | Τιμές | Προεπιλογή | Περιγραφή |
|---|---|---|---|
| `type` | `calculator`, `baseis`, `program`, `sxoli`, `mixanografiko` | (υποχρεωτική) | Ο τύπος του widget. Χωρίς έγκυρο `type` δεν εμφανίζεται τίποτα. |
| `edu` | `gel`, `epal` | `gel` | Το σύστημα: ΓΕΛ ή ΕΠΑΛ. |
| `id` | ακέραιος 1–999999 | – | Ο αριθμός της σχολής. Μόνο για το `sxoli`, όπου είναι υποχρεωτικός: χωρίς έγκυρο `id` δεν εμφανίζεται τίποτα. Στο block ορίζεται από την αναζήτηση σχολής. |
| `pedio` | 1–4 | – | Το πεδίο των Πανελλαδικών. Ισχύει **μόνο για ΓΕΛ**· στο ΕΠΑΛ αγνοείται (και το block δεν εμφανίζει την επιλογή). |
| `theme` | `auto`, `light`, `dark` | `auto` | Το θέμα του widget. |
| `width` | `200`–`2000` (px) ή `10%`–`100%` | `100%` | Το πλάτος. Το `100%` ισοδυναμεί με πλήρες πλάτος. |
| `align` | `left`, `center`, `right` | `left` | Η στοίχιση. Ισχύει μόνο όταν το πλάτος δεν είναι πλήρες. |
| `height` | `auto` ή `200`–`10000` (px) | ανά τύπο | Το ύψος. Αν δεν οριστεί: 720 px για `calculator` και `mixanografiko`, αυτόματο για τους υπόλοιπους. |
| `radius` | 0–48 (px) | `12` | Η καμπυλότητα των γωνιών. |
| `shadow` | `none`, `sm`, `md`, `lg` | `none` | Η σκιά. |
| `border` | `true`, `false` | `false` | Περίγραμμα γύρω από το widget. |
| `lazy` | `true`, `false` | `true` | Φόρτωση όταν το widget πλησιάζει στο παράθυρο προβολής. |

Οι λογικές τιμές δέχονται `1`/`0`, `true`/`false`, `yes`/`no`, `on`/`off`. Στο block, το όνομα της παραμέτρου `id` είναι `schoolId`.

### Πώς φορτώνεται

Σε κάθε σελίδα με widget το plugin φορτώνει ένα μόνο script, το `https://panelladikes.edu.gr/js/embed.v2.js`, με `defer`. Σε σελίδες χωρίς widget δεν φορτώνεται τίποτα. Το ίδιο το widget εμφανίζεται σε iframe που φορτώνεται από το panelladikes.edu.gr, δηλαδή ο επισκέπτης επικοινωνεί απευθείας με αυτό.

## Ενημερώσεις

Το plugin ενημερώνεται από τις εκδόσεις του GitHub (`github.com` και το CDN των αρχείων των εκδόσεων). Κάθε πακέτο ελέγχεται με άθροισμα sha256 και με ψηφιακή υπογραφή Ed25519 πριν την εγκατάσταση· αν ο έλεγχος αποτύχει, η ενημέρωση δεν εγκαθίσταται και εμφανίζεται μήνυμα σφάλματος. Οι ενημερώσεις εμφανίζονται στην οθόνη **Πρόσθετα** και στο **Πίνακας → Ενημερώσεις** όπως όλες οι υπόλοιπες. Οι αυτόματες ενημερώσεις ενεργοποιούνται μόνο αν τις ενεργοποιήσετε εσείς.

## Ιδιωτικότητα και δεδομένα

Το plugin δεν έχει ρυθμίσεις, δεν δημιουργεί options, δεν βάζει cookies και δεν κάνει analytics (οι υπηρεσίες Google της φόρμας email του Μηχανογραφικού, reCAPTCHA και App Check, μπορεί να βάζουν cookies ή να χρησιμοποιούν τοπική αποθήκευση). Κρατά μόνο ένα προσωρινό transient του updater (την περιγραφή της τελευταίας έκδοσης, για 12 ώρες ή 1 ώρα αν αποτύχει η ανάγνωση), που σβήνεται κατά την απεγκατάσταση. Δεν είναι όμως χωρίς επικοινωνία με εξωτερικές υπηρεσίες:

| Πότε | Με ποιον | Τι αποστέλλεται |
|---|---|---|
| Επισκέπτης βλέπει σελίδα με widget | `panelladikes.edu.gr` (script και iframe του widget) | Τα συνήθη στοιχεία κάθε αιτήματος (π.χ. διεύθυνση IP, browser) και, μέσω του Referer, το origin του site σας (`referrerpolicy="origin"`), ώστε να εφαρμοστούν οι ρυθμίσεις του site. Καταμετρώνται προβολές ανά ιστότοπο, τύπο widget και σύστημα, στη μνήμη του server, χωρίς IP, user agent, πλήρη διεύθυνση σελίδας ή αναγνωριστικό επισκέπτη. Το ίδιο το script και το widget δεν κάνουν analytics. |
| Μηχανογραφικό με φόρμα email (όταν η υπηρεσία την έχει ενεργοποιήσει) | Βάση του `panelladikes.edu.gr` (Google Firebase), με Google App Check και reCAPTCHA Enterprise (φορτώνουν scripts της Google) | Λίστα σχολών (αριθμοί), σύστημα, πεδίο ή τομέας, email, γλώσσα και, μόνο με ξεχωριστή επιλογή του επισκέπτη, εγγραφή σε newsletter με την έκδοση του κειμένου συναίνεσης. Δεν στέλνονται όνομα ή βαθμοί. Ο browser γράφει απευθείας στην υπηρεσία: **τα δεδομένα δεν περνούν από το WordPress site σας και δεν αποστέλλονται σε εσάς.** Η λίστα του μαθητή μένει στο `localStorage` του iframe (origin του panelladikes.edu.gr) και μετά την αποστολή μόνο μασκαρισμένο email. Χωρίς ενεργή φόρμα email, το widget προσφέρει μόνο αντιγραφή, εκτύπωση και σύνδεσμο συνέχειας. |
| Επεξεργαστής: προεπισκόπηση του block | `panelladikes.edu.gr` | Τα στοιχεία του αιτήματος του browser του χρήστη του επεξεργαστή. Το script της προεπισκόπησης φορτώνεται στον επεξεργαστή μόνο όταν η σελίδα έχει block του widget. |
| Επεξεργαστής: αναζήτηση σχολής | `search-paneladikesapp.web.app` (απευθείας από τον browser του χρήστη) | Το κείμενο αναζήτησης και το σύστημα (ΓΕΛ/ΕΠΑΛ). Δεν μπαίνουν cookies και δεν στέλνεται referrer· όπως σε κάθε αίτημα προς άλλο origin, ο browser στέλνει την κεφαλίδα Origin (τη διεύθυνση του site) και η υπηρεσία βλέπει τη διεύθυνση IP. Αν η αναζήτηση δεν δουλέψει, μπορείτε να πληκτρολογήσετε τον αριθμό της σχολής. |
| Έλεγχος ενημερώσεων | `github.com` | Μόνο ένα αίτημα GET για ένα στατικό αρχείο, χωρίς στοιχεία του site σας. Το αποτέλεσμα αποθηκεύεται προσωρινά για 12 ώρες. |

Επιπλέον, ο ίδιος ο πυρήνας του WordPress στέλνει στο wordpress.org τη λίστα των plugins του site σε κάθε έλεγχο ενημερώσεων. Αυτό δεν το κάνει το plugin.

## Προσαρμογή εμφάνισης (White-Label)

Οι προσαρμογές της εμφάνισης που δεν ορίζονται από το block ή το shortcode (χρώματα, γραμματοσειρά, η ένδειξη «Powered by») ρυθμίζονται ανά site από την ομάδα του panelladikes.edu.gr. Για να τις ζητήσετε, γράψτε στο [info@panelladikes.edu.gr](mailto:info@panelladikes.edu.gr).

## Συχνά προβλήματα

**Το widget δεν εμφανίζεται ή μένει κενό.**
- Plugins cache ή βελτιστοποίησης με επιλογή «delay JS» ή «defer all scripts» μπορεί να καθυστερούν ή να μπλοκάρουν το script. Εξαιρέστε το `panelladikes.edu.gr/js/embed.v2.js` από αυτή την επιλογή.
- Αν το site στέλνει αυστηρή Content-Security-Policy, πρέπει να επιτρέπει το `https://panelladikes.edu.gr` στο `script-src` και στο `frame-src`.
- Αν το site στέλνει την κεφαλίδα `Referrer-Policy: no-referrer`, το widget αναγνωρίζει το site ως άγνωστο και εμφανίζεται με τις προεπιλεγμένες ρυθμίσεις.
- Για `type="sxoli"` ελέγξτε ότι το `id` είναι έγκυρο· χωρίς αυτό δεν εμφανίζεται τίποτα.

**Ο επεξεργαστής δείχνει μήνυμα ότι η προεπισκόπηση δεν φορτώθηκε.** Το widget θα εμφανίζεται κανονικά στη σελίδα. Ελέγξτε τη σύνδεσή σας και ότι τα εργαλεία αποκλεισμού περιεχομένου του browser δεν μπλοκάρουν το `panelladikes.edu.gr`.

**Η αναζήτηση σχολής δεν δουλεύει.** Πληκτρολογήστε τον αριθμό της σχολής στο πεδίο «School number».

## Υποστήριξη

- Σφάλματα και προτάσεις για τον κώδικα του plugin: [GitHub Issues](https://github.com/PanelladikesApp/panelladikes-wordpress/issues). Δεν δίνεται δέσμευση χρόνου απάντησης.
- Ερωτήσεις για τα δεδομένα ή τα widget: [info@panelladikes.edu.gr](mailto:info@panelladikes.edu.gr).

## Ανάπτυξη

Απαιτούνται PHP 8.2+, [Composer](https://getcomposer.org), Node.js 20+ και, για το τοπικό περιβάλλον WordPress, Docker. Το repo περιέχει `.npmrc` με `legacy-peer-deps=true`, που χρειάζεται για το `npm ci` και το `npm install`.

```bash
composer install
npm install
npm run build
```

| Εντολή | Τι κάνει |
|---|---|
| `npm run build` | Κατασκευάζει το block στον φάκελο `build/`. |
| `npm start` | Κατασκευή σε λειτουργία παρακολούθησης. |
| `npm test` | Ελέγχει το contract και τρέχει τα τεστ των scripts και τα τεστ JavaScript (Vitest). |
| `npm run lint:js` | Έλεγχος JavaScript. |
| `npm run i18n` | Μεταγλώττιση των ελληνικών μεταφράσεων σε `.mo` και `.json` (μετά το `npm run build`· χρειάζεται `wp-env` ή `WP_CLI_PHAR`). |
| `composer test` | Τεστ PHPUnit. |
| `composer lint` | Έλεγχος PHP με τα WordPress Coding Standards (PHPCS). |
| `npx wp-env start` | Τοπικό WordPress με το plugin ενεργό (απαιτεί Docker). |

Το `contract/embed-contract.v2.json` περιγράφει τις επιτρεπτές τιμές των παραμέτρων. Ελέγχεται με άθροισμα sha256 (`npm run contract:check`) και δεν τροποποιείται χειροκίνητα· αν χρειάζεται αλλαγή, ανοίξτε πρώτα ένα issue.

Η δομή του κώδικα:

| Φάκελος | Περιεχόμενο |
|---|---|
| `panelladikes-widgets.php` | Κεντρικό αρχείο του plugin. |
| `includes/` | Κοινός renderer, shortcode, block. |
| `includes/updater/` | Έλεγχος και εγκατάσταση ενημερώσεων. |
| `src/` | Κώδικας του block για τον επεξεργαστή. |
| `contract/` | Προδιαγραφή των παραμέτρων. |
| `languages/` | Μεταφράσεις. |
| `tests/`, `src/test/` | Τεστ PHP και JavaScript. |

Οι εκδόσεις δημιουργούνται και υπογράφονται από τους maintainers (βλ. «Releasing» στο [CONTRIBUTING.md](CONTRIBUTING.md#releasing))· ο συνεισφέρων δεν χρειάζεται κλειδιά.

## Συνεισφορά και ασφάλεια

Δείτε το [CONTRIBUTING.md](CONTRIBUTING.md). Για αναφορά ευπάθειας δείτε το [SECURITY.md](SECURITY.md). Οι αλλαγές ανά έκδοση βρίσκονται στο [CHANGELOG.md](CHANGELOG.md).

## Άδεια

GPL-2.0-or-later, βλ. [LICENSE](LICENSE). Το όνομα και το λογότυπο «Πανελλαδικές / Panelladikes» δεν καλύπτονται από την GPL.

---

## English summary

**Panelladikes Widgets** embeds the widgets of [panelladikes.edu.gr](https://panelladikes.edu.gr) (points calculator, admission bases, exam schedule, school details and Mixanografiko, for both GEL and EPAL) into WordPress with a block or the `[panelladikes]` shortcode.

- **Requirements:** WordPress 6.5+, PHP 8.2+.
- **Install:** download `panelladikes-widgets.zip` from the [latest release](https://github.com/PanelladikesApp/panelladikes-wordpress/releases/latest/download/panelladikes-widgets.zip), then Plugins → Add New → Upload Plugin → Activate. Do not use GitHub's automatic "Source code (zip)".
- **Usage:** `[panelladikes type="calculator"]`, `[panelladikes type="sxoli" edu="gel" id="123"]`. See the parameter table above (same attributes for the block and the shortcode; invalid values fall back to the default; `pedio` applies to GEL only and is ignored for EPAL).
- **Updates:** from GitHub Releases; every package is verified with sha256 and an Ed25519 signature before it is installed.
- **Privacy:** the plugin has no settings, creates no options (it keeps only one temporary update-check transient, removed on uninstall), sets no cookies and runs no analytics. Pages with a widget load a script and an iframe from panelladikes.edu.gr, the block editor loads a live preview from it, and the editor's school search calls `search-paneladikesapp.web.app` from the user's browser. When its email form is enabled by the service, the Mixanografiko widget sends the visitor's email address (and, only by a separate choice, a newsletter subscription) directly from the browser to panelladikes.edu.gr, not through your site and not to the site owner. Site owners should mention this in their privacy policy; see [docs/privacy.md](https://github.com/PanelladikesApp/panelladikes-wordpress/blob/main/docs/privacy.md).
- **Support:** [GitHub Issues](https://github.com/PanelladikesApp/panelladikes-wordpress/issues) for the plugin code, with no response-time commitment; [info@panelladikes.edu.gr](mailto:info@panelladikes.edu.gr) for questions about the data or the widgets.
- **License:** GPL-2.0-or-later. The Panelladikes name and logo are not covered by the GPL.

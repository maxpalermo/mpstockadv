# `<modal-dialog-message>`

Componente custom element per mostrare una modale di messaggio parametrizzabile (info, successo, warning, errore), con stile moderno e contenuto HTML.

## Caratteristiche
- Supporta tipi: info, success, warning, error
- Icone animate SVG in base al tipo
- Titolo e contenuto personalizzabili (anche HTML: tabelle, immagini, ecc.)
- Chiusura solo da pulsante "Chiudi" o tramite timer programmabile
- Non si chiude con ESC o click esterno
- Stile e animazioni moderne, user friendly, Shadow DOM

## Utilizzo base

### 1. Importazione
```js
import { ModalDialogMessageManager } from './modalDialogMessage.js';
```

### 2. Creazione e gestione
```js
const modal = new ModalDialogMessageManager({
    type: 'error', // info | success | warning | error
    title: 'Errore',
    content: '<b>Si è verificato un errore!</b>', // anche HTML
    timer: 3000 // opzionale, chiusura automatica in ms
});

// Cambia tipo, titolo, contenuto, timer
modal.setType('success');
modal.setTitle('Operazione completata');
modal.setContent('Tutto ok!');
modal.setTimer(2000);

// Chiusura manuale
modal.close();
```

## Proprietà/metodi principali
- `setType(type)` — cambia tipo (info, success, warning, error)
- `setTitle(text)` — cambia titolo
- `setContent(html)` — cambia contenuto (anche HTML)
- `setTimer(ms)` — chiusura automatica dopo ms
- `close()` — chiude la modale

## Esempio di test
Vedi il file `test-modalDialogMessage.html` per esempi pratici di utilizzo e personalizzazione.

---

## Note
- Non richiede dipendenze esterne
- Lo stile è incapsulato e non interferisce con il resto della pagina
- Le icone animate aiutano l’utente a riconoscere subito il tipo di messaggio

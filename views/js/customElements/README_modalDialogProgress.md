# `<modal-dialog-progress>`

Componente custom element per mostrare una modale di avanzamento (progress bar) moderna e user friendly.

## Caratteristiche
- Barra di avanzamento animata e colorata
- Titolo e descrizione personalizzabili
- Label di progresso dinamica
- Pulsante "Annulla" che invia segnale di `AbortController`
- Supporto a chiusura programmata e gestione completamento/errore
- Completamente incapsulato con Shadow DOM

## Utilizzo base

### 1. Importazione
```js
import { ModalDialogProgressManager } from './modalDialogProgress.js';
```

### 2. Creazione e gestione
```js
const modal = new ModalDialogProgressManager({
    title: 'Importazione dati',
    description: 'Attendere il completamento dell’operazione...'
});

modal.update(0, 'Preparazione...', 100);

// Aggiorna progresso
modal.update(50, 'A metà strada!', 100);

// Completamento
modal.showDone('Importazione completata!');

// Errore
modal.showError('Errore durante l’importazione');

// Chiusura
modal.close();

// Gestione abort
modal.onAbort(() => {
    // Interrompi le chiamate AJAX usando modal.abortController.signal
});
const abortSignal = modal.abortController.signal;
```

## Proprietà/metodi principali
- `update(progress, label, total)` — aggiorna la barra
- `showDone(message)` — mostra stato completato
- `showError(message)` — mostra errore
- `close()` — chiude la modale
- `onAbort(cb)` — callback su abort
- `abortController.signal` — da passare alle fetch/ajax
- `aborted` — true se annullato

## Esempio di test
Vedi il file `test-modalDialogProgress.html` per un esempio pratico.

---

## Note
- Non richiede dipendenze esterne
- Completamente personalizzabile via JS
- Lo stile è incapsulato e non interferisce con il resto della pagina

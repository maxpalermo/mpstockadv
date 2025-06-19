# `<modern-progress>`

Componente Web moderno per mostrare una barra di avanzamento (progress bar) in una modale personalizzabile. Incapsulamento tramite Shadow DOM, animazioni fluide, gestione abort e stato completato/errore.

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
import { ModernProgressManager } from './modernProgress.js';
```

### 2. Creazione e gestione
```js
const modal = new ModernProgressManager({
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
Consulta il file [`test-modernProgress.html`](./test-modernProgress.html) per un esempio pratico e funzionante.

---

## Note
- Non richiede dipendenze esterne
- Completamente personalizzabile via JS
- Lo stile è incapsulato e non interferisce con il resto della pagina

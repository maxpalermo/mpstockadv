# `<modern-dialog>`

Componente web moderno per mostrare dialog modali personalizzabili, con supporto per messaggi, richieste di conferma (requester), animazioni, stile e icone dinamiche.

## Caratteristiche
- Supporta tipi: info, success, warning, error
- Icone SVG animate in base al tipo
- Titolo e contenuto personalizzabili (anche HTML)
- Chiusura tramite pulsante, timer opzionale, o pulsanti conferma
- Animazioni di apertura/chiusura: `scale-in` (default), `fade-in`, `none` (impostabili tramite `data-animation`)
- Modalità requester/confirm: mostra due pulsanti SI/NO con label e callback/evento custom
- Stile moderno, encapsulato in Shadow DOM

## Utilizzo base

### 1. Importazione
```js
import { ModernDialogManager } from './modernDialog.js';
```

### 2. Creazione e gestione via Manager
```js
const modal = new ModernDialogManager({
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

### 3. Utilizzo diretto del tag custom
```html
<modern-dialog 
  type="success" 
  title="Operazione completata" 
  data-animation="fade-in"
>
  I dati sono stati salvati!
</modern-dialog>
```

## Proprietà/metodi principali
- `setType(type)` — cambia tipo (info, success, warning, error)
- `setTitle(text)` — cambia titolo
- `setContent(html)` — cambia contenuto (anche HTML)
- `setTimer(ms)` — chiusura automatica dopo ms
- `close()` — chiude la modale
- Attributo `data-animation`: `scale-in` (default), `fade-in`, `none`

## Modalità requester/confirm

Per mostrare una richiesta di conferma con SI/NO:
```html
<modern-dialog 
  show-confirm-buttons
  data-callback="onConfirmResult"
  data-yes-label="Conferma"
  data-no-label="Annulla"
  title="Sei sicuro?"
  type="warning"
  data-animation="scale-in"
>
  Sei sicuro di voler continuare?
</modern-dialog>
```
- `show-confirm-buttons`: mostra due pulsanti SI/NO
- `data-yes-label`, `data-no-label`: personalizza le label
- `data-callback`: funzione globale JS da chiamare con 1 (SI) o 0 (NO)
- Se non usi `data-callback`, ascolta l’evento `dialog-result`:
```js
dialog.addEventListener('dialog-result', e => {
  if(e.detail === 1) { /* confermato */ }
  else { /* annullato */ }
});
```

## ModernDialogManager.showConfirm

La funzione statica `ModernDialogManager.showConfirm` permette di mostrare facilmente un dialog di conferma SI/NO, con gestione del risultato tramite callback, evento o Promise. Puoi personalizzare tipo, titolo, testo, etichette e animazione.

### Callback diretta

```js
ModernDialogManager.showConfirm({
  type: 'warning',
  title: 'Sei sicuro?',
  content: 'Vuoi procedere?',
  yesLabel: 'Procedi',
  noLabel: 'Annulla',
  animation: 'scale-in',
  callback: function(result) {
    if(result === 1) alert('Confermato!');
    else alert('Annullato!');
  }
});
```

### Gestione tramite evento

```js
const mgr = ModernDialogManager.showConfirm({
  type: 'warning',
  title: 'Procedere?',
  content: 'Vuoi davvero continuare?'
});
mgr.modal.addEventListener('dialog-result', function(e) {
  if(e.detail === 1) alert('Confermato via evento!');
  else alert('Annullato via evento.');
});
```

### Uso con Promise / async/await

Puoi incapsulare la conferma in una Promise per usarla con async/await:

```js
function confirmDialogPromise(opts) {
  return new Promise(resolve => {
    const mgr = ModernDialogManager.showConfirm(opts);
    mgr.modal.addEventListener('dialog-result', e => resolve(e.detail), { once: true });
  });
}

// Esempio async:
async function chiediConferma() {
  const result = await confirmDialogPromise({
    type: 'info',
    title: 'Conferma richiesta',
    content: 'Vuoi continuare?',
    yesLabel: 'Sì',
    noLabel: 'No',
    animation: 'fade-in'
  });
  if(result === 1) alert('Confermato via Promise!');
  else alert('Annullato via Promise.');
}
```

### Personalizzazione avanzata

Puoi personalizzare ogni aspetto del dialog, incluse le etichette, il tipo, l’animazione e anche il contenuto HTML:

```js
ModernDialogManager.showConfirm({
  type: 'success',
  title: 'Complimenti!',
  content: '<b>Hai completato l\'operazione con successo!</b>',
  yesLabel: 'Grande!',
  noLabel: 'No, riprova',
  animation: 'scale-in',
  callback: function(result) {
    if(result === 1) alert('Risposta positiva personalizzata!');
    else alert('Risposta negativa personalizzata.');
  }
});
```

---

## Esempio di test/demo
Vedi il file `test-modernDialog.html` per esempi pratici di:
- Uso diretto del tag
- Animazioni selezionabili
- Dialog requester/confirm con callback o evento
- Uso di ModernDialogManager.showConfirm per conferme rapide
- Personalizzazione di tipo, titolo, contenuto, timer, animazione, label pulsanti

---

## Note
- Non richiede dipendenze esterne
- Stile e animazioni sono incapsulati nel componente
- Le icone animate aiutano a riconoscere subito il tipo di messaggio
- I dialog requester sono riutilizzabili più volte nella stessa pagina

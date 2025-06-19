# `<product-autocomplete>`

Componente custom element per un campo di autocompletamento prodotti, indipendente, moderno e altamente personalizzabile.

## Caratteristiche

- **Indipendente**: Nessuna dipendenza da jQuery, Select2 o altre librerie esterne.
- **UX Moderna**: Stile unico, animazioni fluide, interfaccia utente chiara.
- **Rendering Personalizzato**: Ogni riga nel dropdown può visualizzare immagini, testo formattato, prezzi, ecc., in base alla struttura dati fornita.
- **AJAX Integrato**: Carica i dati da un endpoint configurabile.
- **Selezione Singola o Multipla**: Supporta entrambe le modalità.
  - In modalità multipla, gli elementi selezionati scompaiono dal dropdown e riappaiono se deselezionati.
- **Controllo da Tastiera**: Navigazione nel dropdown con frecce, Invio per selezionare, Esc per chiudere.
- **Configurabile tramite Attributi**: `endpoint`, `placeholder`, `max-results`, `min-length`, `multiple`.
- **Eventi Custom**: `selected` (quando un item è selezionato/aggiunto o la lista dei multipli cambia), `deselected` (quando un item è rimosso in modalità multipla).
- **Shadow DOM**: Stile e struttura incapsulati.

## Utilizzo Base

### 1. Inclusione nel HTML

```html
<product-autocomplete 
    id="mioAutocomplete"
    endpoint="/api/cerca-prodotti"
    placeholder="Cerca un prodotto..."
    max-results="7"
    min-length="2"
    multiple
></product-autocomplete>
```

### 2. Gestione Eventi (JavaScript)

```js
const autocomplete = document.getElementById('mioAutocomplete');

autocomplete.addEventListener('selected', (event) => {
    console.log('Elemento(i) selezionato(i):', event.detail);
    // event.detail sarà l'oggetto singolo o l'array di oggetti selezionati
});

autocomplete.addEventListener('deselected', (event) => {
    console.log('Elemento deselezionato:', event.detail);
    // event.detail sarà l'oggetto deselezionato (solo in modalità multipla)
});
```

## Struttura Dati Endpoint

L'endpoint deve restituire un array JSON di oggetti. Ogni oggetto deve avere:

- `id`: Un identificatore univoco.
- `label`: Può essere:
  - Una **stringa semplice**: Verrà mostrata direttamente.
  - Un **array di oggetti** per rendering personalizzato. Ogni oggetto nell'array `label` può avere:
    - `value` (string): Il testo da visualizzare o il dato nascosto.
    - `type` (string, opzionale): Tipo di elemento per styling/struttura speciale. Tipi riconosciuti internamente:
      - `image`: `src` (string) conterrà l'URL dell'immagine. Il `value` può essere usato per l'attributo `alt`.
      - `price`: `value` sarà formattato come prezzo.
      - `hidden`: Il `value` non verrà visualizzato ma sarà incluso nei dati dell'oggetto selezionato.
      - Nessun `type` o `type: 'name'` è considerato testo principale.
    - `src` (string, opzionale): Usato se `type: 'image'`.

**Esempio Oggetto Risultato:**

```json
{
  "id": 123,
  "label": [
    { "type": "image", "src": "/path/to/image.jpg", "value": "Logo Prodotto" },
    { "value": "Nome Prodotto Fantastico" },
    { "value": "Taglia: M / Colore: Blu" },
    { "type": "price", "value": "€49.99" },
    { "type": "hidden", "value": "SKU_ABC123" }
  ]
}
```

OPPURE

```json
{
  "id": 124,
  "label": "Prodotto Semplice"
}
```

## Attributi HTML

- `endpoint` (string, obbligatorio): URL da cui caricare i risultati.
- `placeholder` (string, opzionale): Testo segnaposto per l'input. Default: "Cerca...".
- `max-results` (number, opzionale): Numero massimo di risultati da mostrare nel dropdown. Default: 10.
- `min-length` (number, opzionale): Lunghezza minima del testo prima di avviare la ricerca. Default: 2.
- `multiple` (boolean, opzionale): Se presente, abilita la selezione multipla.

## Proprietà e Metodi JavaScript

- `value` (getter/setter): Ottiene o imposta l'elemento/gli elementi selezionati.
  - Per selezione singola: `autocomplete.value = {id: 1, label: '...'};`
  - Per selezione multipla: `autocomplete.value = [{id: 1, label: '...'}, {id: 2, label: '...'}];`
- `selected` (getter): Alias di `value`.
- `reset()`: Resetta il campo, deselezionando tutti gli elementi e svuotando l'input.

## Esempio di Test

Vedi il file `test-productAutocomplete.html` per un esempio pratico con dati fittizi e diverse configurazioni.

---

## Note

- Il componente gestisce internamente il debounce per le chiamate AJAX per non sovraccaricare il server (implicito nel modo in cui gestisce l'input e le chiamate fetch con AbortController).
- Lo stile è moderno e pensato per una buona UX, ma può essere ulteriormente personalizzato modificando gli stili interni al componente (se necessario).


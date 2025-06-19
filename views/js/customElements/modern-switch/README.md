# Modern Switch Component

Un componente Web Component per creare switch moderni e personalizzabili con animazioni fluide.

## Installazione

1. Importa il componente nel tuo HTML:
```html
<script src="modern-switch.js" type="module"></script>
```

2. Usa il tag `<modern-switch>` nel tuo HTML:
```html
<modern-switch></modern-switch>
```

## Attributi

### Attributi Base
- `checked`: Imposta lo stato iniziale dello switch
- `disabled`: Disabilita l'interazione con lo switch

```html
<!-- Switch pre-selezionato -->
<modern-switch checked></modern-switch>

<!-- Switch disabilitato -->
<modern-switch disabled></modern-switch>
```

### Label e Colori
- `data-label-on`: Testo da mostrare quando lo switch è attivo
- `data-label-off`: Testo da mostrare quando lo switch è disattivo
- `data-color-on`: Colore del testo quando lo switch è attivo
- `data-color-off`: Colore del testo quando lo switch è disattivo
- `data-style`: Colore dello switch quando attivo. Può essere:
  - Uno dei valori predefiniti: `success`, `info`, `warning`, `error`
  - Un codice colore CSS valido (es. `#F57022`)

```html
<!-- Switch con label -->
<modern-switch
    data-label-on="ONLINE"
    data-label-off="OFFLINE"
    data-color-on="#2196F3"
    data-color-off="#666"
></modern-switch>
```

### Posizionamento Label
- `data-label-position`: Posizione della label rispetto allo switch
  - `"right"` (default)
  - `"left"`
  - `"top"`
  - `"bottom"`

```html
<!-- Label sopra lo switch -->
<modern-switch
    data-label-position="top"
    data-label-on="ACCESO"
    data-label-off="SPENTO"
></modern-switch>
```

## Metodi

### getValue()
Restituisce lo stato corrente dello switch come valore numerico:
- `1`: switch attivo
- `0`: switch disattivo

```javascript
const switch = document.querySelector('modern-switch');
const value = switch.getValue(); // returns 1 o 0
```

### setState(value)
Imposta lo stato dello switch. Accetta diversi tipi di input:
- Numeri: `1` o `0`
- Booleani: `true` o `false`
- Stringhe: `"1"`, `"0"`, `"true"`, `"false"`

```javascript
// Attiva lo switch
switch.setState(1);
switch.setState(true);
switch.setState('1');
switch.setState('true');

// Disattiva lo switch
switch.setState(0);
switch.setState(false);
switch.setState('0');
switch.setState('false');

// Supporta method chaining
switch.setState(1).getValue(); // returns 1
```

## Eventi

Il componente emette un evento `change` quando lo stato cambia (sia tramite click che tramite setState):

```javascript
const switch = document.querySelector('modern-switch');
switch.addEventListener('change', (event) => {
    const isChecked = event.detail.checked;
    console.log('Nuovo stato:', isChecked ? 'attivo' : 'disattivo');
});
```

## Proprietà JavaScript

È possibile controllare lo switch programmaticamente:

```javascript
const switch = document.querySelector('modern-switch');

// Leggi lo stato
console.log(switch.checked);

// Imposta lo stato
switch.checked = true;
```

## Stile

Il componente include già uno stile predefinito con:
- Transizioni fluide per il cambio di stato
- Feedback tattile al click
- Colori moderni
- Design responsive

## Esempi

### Switch Base
```html
<modern-switch></modern-switch>
```

### Switch con Label a Destra
```html
<modern-switch
    data-label-on="Attivo"
    data-label-off="Disattivo"
></modern-switch>
```

### Switch con Label Colorata
```html
<modern-switch
    data-label-on="ON"
    data-label-off="OFF"
    data-color-on="#2196F3"
    data-color-off="#666"
></modern-switch>
```

### Switch con Stile Predefinito
```html
<modern-switch
    data-style="success"
    data-label-on="ATTIVO"
    data-label-off="DISATTIVO"
></modern-switch>
```

### Switch con Colore Personalizzato
```html
<modern-switch
    data-style="#F57022"
    data-label-on="CUSTOM"
    data-label-off="CUSTOM"
></modern-switch>
```

### Switch con Label in Alto
```html
<modern-switch
    data-label-position="top"
    data-label-on="ACCESO"
    data-label-off="SPENTO"
></modern-switch>
```

### Switch Disabilitato con Label
```html
<modern-switch
    disabled
    data-label-on="SI"
    data-label-off="NO"
></modern-switch>
```

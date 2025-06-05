# Custom Element: `<modern-toolbar>`

Il componente `<modern-toolbar>` è una toolbar web moderna e personalizzabile, progettata per offrire un'interfaccia utente ricca e interattiva. Permette di visualizzare un logo, un menu di navigazione dinamico (con supporto per icone Material Icons e sottomenu annidati), pulsanti di azione e una sezione di ricerca.

## Caratteristiche

-   **Logo Personalizzabile**: Visualizza un logo all'estrema sinistra.
-   **Menu Dinamico**:
    -   Configurabile tramite JSON.
    -   Supporto per icone (Material Icons).
    -   Supporto per sottomenu annidati (apertura al passaggio del mouse).
    -   Possibilità di definire link `href` o azioni custom (`onclickAction`) per gli item.
    -   Colori di sfondo e testo personalizzabili per singolo item.
-   **Pulsanti Azione**:
    -   Configurabili tramite JSON.
    -   Supporto per icone (Material Icons).
    -   Azioni definite tramite `actionId`.
    -   Colori di sfondo e testo personalizzabili per singolo pulsante.
-   **Sezione Ricerca**:
    -   Campo di input per la ricerca.
    -   Pulsante di ricerca con icona.
-   **Event-Driven**: Emette eventi custom per interazioni:
    -   `menu-item-click`: Quando un item del menu (senza `href` diretto) viene cliccato.
    -   `action-button-click`: Quando un pulsante azione viene cliccato.
    -   `toolbar-search`: Quando viene inviata una ricerca.
-   **Shadow DOM**: Stili incapsulati per evitare conflitti con la pagina ospite.
-   **Classe Manager**: `ModernToolbarManager` per una facile inizializzazione e gestione.

## Prerequisiti

-   Le icone Material Icons sono caricate automaticamente dal componente. Non è necessario includere il link a Google Fonts nella pagina ospite.

## Installazione e Utilizzo

1.  **Includi lo Script**:
    Assicurati che il file `modernToolbar.js` sia accessibile e includilo nella tua pagina HTML:
    ```html
    <script type="module" src="path/to/modernToolbar.js"></script>
    ```

2.  **Crea un Contenitore**:
    Aggiungi un elemento HTML che fungerà da contenitore per la toolbar:
    ```html
    <div id=\"myToolbarContainer\"></div>
    ```

3.  **Inizializza con `ModernToolbarManager`**:
    Utilizza la classe `ModernToolbarManager` per configurare e inizializzare la toolbar.

    ```html
    <script type="module">
        import { ModernToolbarManager } from './path/to/modernToolbar.js';

        // Dati di configurazione per il menu
        const menuData = [
            {
                icon: "home", // Icona Material Icons (opzionale)
                label: "Home",
                href: "#home" // Link diretto (opzionale)
            },
            {
                icon: "settings",
                label: "Impostazioni",
                // onclickAction: "openSettingsModal", // Azione custom (opzionale, se non c'è href)
                children: [ // Sottomenu (opzionale)
                    { icon: "person", label: "Profilo", href: "#profilo" },
                    {
                        icon: "palette",
                        label: "Preferenze Tema",
                        background: "#4CAF50", // Colore sfondo item (opzionale)
                        color: "white",       // Colore testo item (opzionale)
                        children: [
                            { icon: "brightness_5", label: "Chiaro", onclickAction: "setThemeLight" },
                            { icon: "brightness_2", label: "Scuro", onclickAction: "setThemeDark" }
                        ]
                    }
                ]
            },
            {
                label: "Aiuto", // Item senza icona né href, emetterà menu-item-click
                onclickAction: "showHelp"
            }
        ];

        // Dati di configurazione per i pulsanti azione
        const actionButtonsData = [
            {
                icon: "add_circle",
                label: "Nuovo",
                actionId: "createNewItem" // ID per identificare l'azione nell'evento
            },
            {
                icon: "save",
                label: "Salva Modifiche",
                actionId: "saveDocument",
                background: "#ff9800", // Colore sfondo pulsante (opzionale)
                color: "white"        // Colore testo pulsante (opzionale)
            }
        ];

        // Istanzia il manager
        const toolbarManager = new ModernToolbarManager({
            targetElement: '#myToolbarContainer', // Selettore CSS del contenitore
            logoSrc: 'path/to/your/logo.png',   // URL del logo (opzionale)
            menuItems: menuData,                // Array di oggetti per il menu
            actionButtons: actionButtonsData,   // Array di oggetti per i pulsanti azione (opzionale)
            searchPlaceholder: "Cerca qui...", // Placeholder per la ricerca (opzionale)
            showSearch: true // Default è true, impostare a false per nascondere la ricerca
        });

        // Ottieni l'istanza del custom element per aggiungere event listener
        const toolbarElement = toolbarManager.getToolbarInstance();

        // Gestione Eventi
        toolbarElement.addEventListener('menu-item-click', (e) => {
            console.log('Menu item cliccato:', e.detail);
            // e.detail contiene l'oggetto completo dell'item cliccato
            if (e.detail.onclickAction === 'showHelp') {
                alert('Mostra aiuto!');
            }
        });

        toolbarElement.addEventListener('action-button-click', (e) => {
            console.log('Pulsante azione cliccato:', e.detail);
            // e.detail contiene l'oggetto completo del pulsante cliccato
            if (e.detail.actionId === 'createNewItem') {
                // Logica per creare un nuovo item
            }
        });

        toolbarElement.addEventListener('toolbar-search', (e) => {
            console.log('Ricerca inviata:', e.detail);
            // e.detail è un oggetto: { query: "testo cercato" }
            alert(`Hai cercato: ${e.detail.query}`);
        });

    </script>
    ```

## Struttura Dati di Configurazione

### Oggetto di Configurazione per `ModernToolbarManager`

-   `targetElement` (String, obbligatorio): Selettore CSS dell'elemento che conterrà la toolbar.
-   `logoSrc` (String, opzionale): URL dell'immagine del logo. Se non fornito, l'area logo non viene renderizzata.
-   `menuItems` (Array<Object>, obbligatorio): Array di oggetti che definiscono le voci del menu principale.
-   `actionButtons` (Array<Object>, opzionale): Array di oggetti che definiscono i pulsanti di azione.
-   `searchPlaceholder` (String, opzionale): Testo placeholder per il campo di ricerca. Default: "Cerca...".
-   `showSearch` (Boolean, opzionale): Se `false`, la sezione di ricerca non viene renderizzata. Default: `true`.

### Oggetto Item Menu (`menuItems`)

Ogni oggetto nell'array `menuItems` può avere le seguenti proprietà:

-   `label` (String, obbligatorio): Testo visualizzato per l'item.
-   `icon` (String, opzionale): Nome dell'icona Material Icons (es. "home", "settings").
-   `href` (String, opzionale): URL a cui l'item deve puntare. Se presente, l'item si comporterà come un link standard.
-   `onclickAction` (String, opzionale): Un identificatore per un'azione custom. Usato se `href` non è presente. L'evento `menu-item-click` includerà questo valore.
-   `children` (Array<Object>, opzionale): Un array di oggetti item menu per creare un sottomenu.
-   `background` (String, opzionale): Colore di sfondo CSS per l'item (es. "#FF0000", "blue").
-   `color` (String, opzionale): Colore del testo CSS per l'item.

### Oggetto Pulsante Azione (`actionButtons`)

Ogni oggetto nell'array `actionButtons` può avere le seguenti proprietà:

-   `label` (String, obbligatorio): Testo visualizzato per il pulsante.
-   `icon` (String, opzionale): Nome dell'icona Material Icons.
-   `actionId` (String, obbligatorio): Identificatore univoco per l'azione del pulsante. L'evento `action-button-click` includerà questo valore.
-   `background` (String, opzionale): Colore di sfondo CSS per il pulsante.
-   `color` (String, opzionale): Colore del testo CSS per il pulsante.

## Eventi Custom

Il componente `<modern-toolbar>` emette i seguenti eventi custom:

-   **`menu-item-click`**:
    -   Scatenato quando un item del menu (che non ha un `href`) viene cliccato.
    -   `event.detail`: Contiene l'oggetto completo dell'item di menu cliccato.

-   **`action-button-click`**:
    -   Scatenato quando un pulsante di azione viene cliccato.
    -   `event.detail`: Contiene l'oggetto completo del pulsante di azione cliccato.

-   **`toolbar-search`**:
    -   Scatenato quando viene inviata una ricerca (premendo Invio nel campo di ricerca o cliccando il pulsante di ricerca).
    -   `event.detail`: Un oggetto `{ query: "testo della ricerca" }`.

## Esempio Completo

Vedi il file `test-modernToolbar.html` per un esempio pratico di implementazione e gestione degli eventi.

## Stile

Gli stili principali sono incapsulati nello Shadow DOM. È possibile sovrascrivere alcune variabili CSS se esposte dal componente (attualmente non implementato, ma possibile futura feature) o targettizzare il componente stesso per stili di layout esterni.

# ModernTable Web Component

## Formattazioni disponibili (data-render-function)

Puoi utilizzare l'attributo `data-render-function` nei `<th>` per formattare automaticamente le colonne. Ecco le funzioni disponibili:

| Funzione            | Descrizione                                         | Esempio output                | Attributo da usare                |
|---------------------|-----------------------------------------------------|-------------------------------|------------------------------------|
| `formatCurrency`    | Valuta Euro con separatore italiano                 | 1.234,56 €                    | `data-render-function="formatCurrency"` |
| `formatPercent`     | Percentuale con due decimali                        | 12.34%                        | `data-render-function="formatPercent"`  |
| `formatDate`        | Data in formato locale italiano                     | 05/06/2025                    | `data-render-function="formatDate"`     |
| `formatDateTime`    | Data e ora in formato locale italiano               | 05/06/2025 15:32:00           | `data-render-function="formatDateTime"` |
| `formatBoolean`     | Booleano (✔️/✖️)                                     | ✔️ oppure ✖️                  | `data-render-function="formatBoolean"`  |
| `formatInteger`     | Numero intero con separatore italiano               | 1.234                         | `data-render-function="formatInteger"`  |
| `formatFloat`       | Numero decimale con 2 decimali, separatore italiano | 1.234,56                      | `data-render-function="formatFloat"`    |

### Esempio di intestazione HTML

```html
<modern-table>
  <table>
    <thead>
      <tr>
        <th data-key="item_name">Articolo</th>
        <th data-key="quantity" data-align="center" data-render-function="formatInteger">Quantità</th>
        <th data-key="unit_price" data-align="right" data-render-function="formatCurrency">Prezzo Unitario</th>
        <th data-key="percentuale" data-render-function="formatPercent">Sconto</th>
        <th data-key="data" data-render-function="formatDate">Data</th>
        <th data-key="dataora" data-render-function="formatDateTime">Data/Ora</th>
        <th data-key="attivo" data-render-function="formatBoolean">Attivo</th>
        <th data-key="peso" data-render-function="formatFloat">Peso</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Libro</td>
        <td>1234</td>
        <td>25.00</td>
        <td>0.15</td>
        <td>2025-06-05</td>
        <td>2025-06-05T15:32:00</td>
        <td>1</td>
        <td>3.20</td>
      </tr>
    </tbody>
  </table>
</modern-table>
```

Puoi combinare `data-align` e `data-render-function` per ottenere la formattazione e l'allineamento desiderati.

## Features

* **Data Binding**: Can be populated with data either through a `data` property (JavaScript array of objects) or by providing an `endpoint` attribute for remote data fetching.
* **Column Configuration**: Columns are defined via a `columns` property (JavaScript array of objects) or by declaring `<modern-table-column>` child elements.
  * Each column can specify its `key` (matching a key in the data objects), `label` (for the header), `sortable` status, and a custom `renderFunction` for cell content.
* **Pagination**: Supports client-side and server-side pagination.
  * `page-size` attribute to control items per page.
  * `pagination-buttons` attribute to show/hide previous/next buttons.
* **Sorting**: Clickable column headers for sorting (if `sortable` is true).
  * Dispatches a `modern-table-sort` event with sort details.
* **Custom Cell Rendering**: Allows defining custom JavaScript functions to render cell content, providing flexibility in how data is displayed (e.g., formatting dates, adding buttons, images).
* **Row Actions**: Supports a special column type for actions (e.g., edit, delete buttons) per row using a `renderFunction` that returns HTML for the action buttons.
* **Loading State**: Displays a loading indicator when fetching data remotely.
* **No Data Message**: Shows a customizable message when there's no data to display.
* **Styling**: Uses Shadow DOM for encapsulation and can be styled using CSS custom properties.
* **Accessibility**: Basic ARIA attributes for table structure.

## Usage

### Basic Example (Local Data)

```html
<modern-table id="my-table" page-size="5">
</modern-table>

<script>
  const table = document.getElementById('my-table');
  table.columns = [
    { key: 'id', label: 'ID', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email' }
  ];
  table.data = [
    { id: 1, name: 'Alice', email: 'alice@example.com' },
    { id: 2, name: 'Bob', email: 'bob@example.com' },
    // ... more data
  ];
</script>
```

### Example with Remote Data and Custom Rendering

```html
<modern-table
  endpoint="/api/data"
  page-size="10"
  pagination-buttons="true"
  id="remote-table"
>
  <modern-table-column key="productName" label="Product" sortable="true"></modern-table-column>
  <modern-table-column key="price" label="Price" sortable="true" data-render-function="formatCurrency"></modern-table-column>
  <modern-table-column key="actions" label="Actions" data-render-function="renderRowActions"></modern-table-column>
</modern-table>

<script>
  // Make these functions available globally or on a shared object
  window.formatCurrency = function(value, rowData) {
    return `$${Number(value).toFixed(2)}`;
  }
  window.renderRowActions = function(value, rowData) {
    return `<button data-tooltip-text="Edit ${rowData.productName}" onclick="editItem(${rowData.id})">Edit</button>
            <button data-tooltip-text="Delete ${rowData.productName}" onclick="deleteItem(${rowData.id})">Delete</button>`;
  }

  function editItem(id) { console.log('Edit:', id); }
  function deleteItem(id) { console.log('Delete:', id); }

  // Listen for events
  const remoteTable = document.getElementById('remote-table');
  remoteTable.addEventListener('modern-table-sort', (e) => {
    console.log('Sort event:', e.detail);
    // If using server-side sorting, you might re-fetch data here with sort parameters
  });
  remoteTable.addEventListener('modern-table-page-change', (e) => {
    console.log('Page change event:', e.detail);
    // If using server-side pagination, re-fetch data for the new page
  });
</script>
```

## Attributes

* `endpoint`: URL to fetch data from (expects JSON array of objects).
* `page-size`: Number of items per page (default: 10).
* `pagination-buttons`: (true/false) Show/hide pagination controls (default: true).
* `no-data-message`: Message to display when table is empty (default: "No data available").
* `loading-message`: Message to display while loading (default: "Loading...").

## Properties (JavaScript API)

* `data`: (Array) Sets or gets the table data.
* `columns`: (Array) Sets or gets the column definitions.
* `currentPage`: (Number) Sets or gets the current page number.
* `refreshData()`: Method to manually trigger a data refresh (useful after CUD operations if data is remote).

## `<modern-table-column>` Child Element Attributes

* `key`: (Required) The key in the data object for this column.
* `label`: (Required) The text to display in the column header.
* `sortable`: (true/false) Whether the column is sortable (default: false).
* `data-render-function`: Name of a global JavaScript function to render the cell content. The function receives `(value, rowData, cellElement)` as arguments.
* `header-class`: CSS class to apply to the header cell (`<th>`).
* `cell-class`: CSS class to apply to the body cells (`<td>`).

## Events

* `modern-table-sort`: Fired when a sortable column header is clicked.
  * `detail`: `{ key: string, direction: 'asc' | 'desc' }`
* `modern-table-page-change`: Fired when the page changes.
  * `detail`: `{ currentPage: number, pageSize: number }`
* `modern-table-row-click`: Fired when a row is clicked (excluding clicks on interactive elements within cells like buttons).
  * `detail`: `{ rowData: object, rowIndex: number, originalEvent: Event }`
* `modern-table-data-loaded`: Fired after data is successfully loaded and rendered.
  * `detail`: `{ data: Array }`
* `modern-table-data-error`: Fired if an error occurs during remote data fetching.
  * `detail`: `{ error: Error }`

## Styling

Basic styling is applied via Shadow DOM. You can customize the appearance using CSS custom properties (to be documented, common ones might include `--table-header-bg`, `--table-row-even-bg`, etc.) or by targeting the component itself for overall layout.

```css
modern-table {
  --table-border-color: #ccc;
  --table-header-bg: #f0f0f0;
  --table-header-text-color: #333;
  --table-row-hover-bg: #e9e9e9;
  width: 100%;
  display: block; /* Ensures it takes up block space */
}
```

## Tooltips

The component has built-in support for custom tooltips on elements within cells (e.g., action buttons) if they have a `data-tooltip-text` attribute. This avoids conflicts with native `title` attributes and allows for richer tooltip styling if needed.

```html
<!-- Inside a renderFunction for a cell -->
<button data-tooltip-text="More details about this item">Info</button>
```

This documentation provides a comprehensive overview of the `modern-table` component. Further details on specific implementations or advanced configurations might require inspecting the source code or more specific examples.

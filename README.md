# mpstockadv

## Modifica richiesta alla tabella stock_mvt

Per il corretto funzionamento del modulo **mpstockadv**, è necessario aggiungere due nuovi campi alla tabella `stock_mvt` nel database:

- `stock_before` INT NOT NULL DEFAULT 0
- `stock_after` INT NOT NULL DEFAULT 0

Questi campi sono utilizzati per tracciare la quantità di stock prima e dopo ogni movimento di magazzino. Se non presenti, alcune funzionalità del modulo potrebbero non funzionare correttamente.


Gestione avanzata del magazzino di Prestashop v8

## Ricerca prodotto e struttura form movimento

Nel form "Movimento di magazzino" sono presenti i seguenti campi (input e select):

<table>
  <thead>
    <tr>
      <th>Tipo HTML</th>
      <th>id</th>
      <th>name</th>
      <th>Hidden</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>image</td><td>stock-mvt-product-image</td><td>--</td><td>No</td></tr>
    <tr><td>input hidden</td><td>id_employee</td><td>id_employee</td><td>Sì</td></tr>
    <tr><td>select</td><td>id_stock_mvt_reason</td><td>id_stock_mvt_reason</td><td>No</td></tr>
    <tr><td>input hidden</td><td>sign</td><td>sign</td><td>Sì</td></tr>
    <tr><td>select</td><td>id_warehouse</td><td>id_warehouse</td><td>No</td></tr>
    <tr><td>select</td><td>id_product</td><td>id_product</td><td>No</td></tr>
    <tr><td>input hidden</td><td>reference</td><td>reference</td><td>Sì</td></tr>
    <tr><td>input hidden</td><td>ean13</td><td>ean13</td><td>Sì</td></tr>
    <tr><td>input hidden</td><td>upc</td><td>upc</td><td>Sì</td></tr>
    <tr><td>input hidden</td><td>mpn</td><td>mpn</td><td>Sì</td></tr>
    <tr><td>input hidden</td><td>id_product_attribute</td><td>id_product_attribute</td><td>Sì</td></tr>
    <tr><td>input text readonly</td><td>product_attribute_name</td><td>product_attribute_name</td><td>No</td></tr>
    <tr><td>input number</td><td>physical_quantity</td><td>physical_quantity</td><td>No</td></tr>
    <tr><td>input number</td><td>price_te</td><td>price_te</td><td>No</td></tr>
    <tr><td>input number readonly</td><td>tax_rate</td><td>tax_rate</td><td>No</td></tr>
    <tr><td>input number</td><td>price_ti</td><td>price_ti</td><td>No</td></tr>
    <tr><td>input number readonly</td><td>last_wa</td><td>last_wa</td><td>No</td></tr>
    <tr><td>input number readonly</td><td>current_wa</td><td>current_wa</td><td>No</td></tr>
  </tbody>
</table>

Questa tabella ti aiuta a identificare rapidamente tutti i campi coinvolti nella gestione dei movimenti di magazzino, utili anche per implementare logiche di autocompletamento, validazione o integrazione JS.

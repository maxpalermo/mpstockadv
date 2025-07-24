# Struttura XML: Confronto tra order.xml, delivery.xml, invoice.xml

## Struttura generale comune

```xml
<invoices>
  <invoice>
    <document_type/>
    <order_id/>
    <order_date/>
    <order_reference/>
    <current_status/>
    <invoice_id/>
    <invoice_number/>
    <invoice_date/>
    <products_tax_excl/>
    <discounts_tax_excl/>
    <shipping_tax_excl/>
    <wrapping_tax_excl/>
    <products_tax_incl/>
    <discounts_tax_incl/>
    <shipping_tax_incl/>
    <wrapping_tax_incl/>
    <total_tax_excl/>
    <total_taxes/>
    <total_tax_incl/>
    <total_paid/>
    <vat_code/>
    <rounds/>
    <nc/>
    <payment/>
    <carrier/>
    <shop_address/>
    <foreign/>
    <discount_note/>
    <customer>
      <id/>
      <id_customer/>
      <gender/>
      <firstname/>
      <lastname/>
      <birthday/>
      <pec/>
      <uid/>
      <email/>
      <new/>
      <address_delivery>
        <subject/>
        <company/>
        <firstname/>
        <lastname/>
        <address1/>
        <address2/>
        <postcode/>
        <city/>
        <state_name/>
        <country_name/>
        <phone/>
        <phone_mobile/>
        <vat_number/>
        <dni/>
        <state/>
        <country/>
      </address_delivery>
      <address_invoice>
        <subject/>
        <company/>
        <firstname/>
        <lastname/>
        <address1/>
        <address2/>
        <postcode/>
        <city/>
        <state_name/>
        <country_name/>
        <phone/>
        <phone_mobile/>
        <vat_number/>
        <dni/>
        <state/>
        <country/>
      </address_invoice>
    </customer>
    <rows>
      <row>
        <ean13/>
        <reference/>
        <original_price_tax_excl/>
        <product_price_tax_excl/>
        <original_price_tax_incl/>
        <discount_percent/>
        <reduction_amount/>
        <price_tax_excl/>
        <unit_price_tax_excl/>
        <unit_price_tax_incl/>
        <qty/>
        <total_tax_excl/>
        <total_price_tax_excl/>
        <total_price_tax_incl/>
        <total_tax_incl/>
        <tax_rate/>
        <product_id/>
        <product_attribute_id/>
        <product_check_qty>
          <employee/>
        </product_check_qty>
      </row>
      <!-- altri row... -->
    </rows>
  </invoice>
</invoices>
```

## Differenze di struttura

Dall’analisi dei file `order.xml`, `delivery.xml` e `invoice.xml` **non emergono differenze strutturali**: tutti condividono la stessa gerarchia di tag principali e secondari. 

Le uniche differenze riscontrabili sono:
- Il valore di `<document_type>` che identifica il tipo di documento (ordine, consegna, fattura)
- Alcuni tag potrebbero essere vuoti o non valorizzati a seconda del documento, ma la loro presenza nella struttura è garantita.

**Nota:** Se in futuro venissero aggiunti tag opzionali specifici per uno solo dei tipi di documento, questi andrebbero evidenziati qui.

---

_Analisi aggiornata al 10/07/2025._

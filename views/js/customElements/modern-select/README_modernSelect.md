# ModernSelect Web Component

`modern-select` is a custom web component that provides an enhanced, accessible, and styleable alternative to the native HTML `<select>` element. It supports single and multiple selections, local and remote data sources, filtering, and keyboard navigation.

## Features

*   **Single and Multiple Selection**: Supports both modes via the `multiple` attribute.
*   **Local Data**: Options can be provided as slotted `<div>` elements or a JavaScript array.
*   **Remote Data**: Fetch options dynamically from an `endpoint` URL.
*   **Filtering/Search**: Built-in search input to filter options.
*   **Keyboard Navigation**: Full keyboard support for opening, closing, navigating options (ArrowUp, ArrowDown, Home, End), selecting (Enter, Space), and escaping.
*   **Accessibility**: ARIA attributes for roles, states (e.g., `aria-expanded`, `aria-activedescendant`), and relationships to ensure usability with assistive technologies.
*   **Customizable Appearance**: Uses Shadow DOM for encapsulation. Basic styling is provided, and key aspects can be customized via CSS custom properties.
*   **Placeholder**: Customizable placeholder text.
*   **"No Results" Message**: Customizable message when no options match the filter or if data is empty.
*   **Selected Item Display**: Shows the selected option(s). For multiple selections, items are displayed as "pills" with remove buttons.
*   **Programmatic Control**: Public API methods to get/set value, clear selection, etc.
*   **Custom Events**: Dispatches events for changes in selection.

## Usage

### Basic Single Select (Local Data via Slots)

```html
<modern-select placeholder="Choose an option">
  <div slot="option" data-value="val1">Option 1</div>
  <div slot="option" data-value="val2">Option 2</div>
  <div slot="option" data-value="val3">Option 3</div>
</modern-select>
```

### Multiple Select (Local Data via JavaScript)

```html
<modern-select id="multi-local" multiple placeholder="Select items"></modern-select>

<script>
  const multiSelect = document.getElementById('multi-local');
  const items = [
    { value: 'apple', label: 'Apple' },
    { value: 'banana', label: 'Banana' },
    { value: 'cherry', label: 'Cherry' }
  ];
  // This assumes a method to populate options from JS, e.g., by dynamically creating slotted divs
  // Or, if the component supports a .options property:
  // multiSelect.options = items; // (Hypothetical, check component's actual API for this)

  // For now, you'd typically add slotted elements dynamically:
  items.forEach(item => {
    const optionEl = document.createElement('div');
    optionEl.setAttribute('slot', 'option');
    optionEl.setAttribute('data-value', item.value);
    optionEl.textContent = item.label;
    multiSelect.appendChild(optionEl);
  });
</script>
```

### Single Select (Remote Data)

```html
<modern-select
  id="remote-single"
  endpoint="/api/categories"
  placeholder="Select a category"
  data-value-field="id"      <!-- Optional: field in remote data for option's value -->
  data-label-field="name"    <!-- Optional: field in remote data for option's display text -->
  data-max-results="10"     <!-- Optional: max results to show in dropdown -->
>
</modern-select>
```

## Attributes

*   `placeholder`: Text to display when no option is selected (default: "Select an option").
*   `multiple`: (Boolean attribute) If present, allows multiple selections.
*   `endpoint`: URL to fetch options from. The component expects a JSON array of objects.
*   `data-value-field`: The name of the property in fetched data objects to use as the option's value (default: `value`).
*   `data-label-field`: The name of the property in fetched data objects to use as the option's display label (default: `label`).
*   `no-results-text`: Text to display when no options are available or match the search (default: "No results found").
*   `max-results`: Maximum number of options to display in the dropdown (default: 100). Useful for performance with large datasets.
*   `disabled`: (Boolean attribute) If present, disables the select component.

## Slotted Content

*   `option`: Use `<div slot="option" data-value="your-value">Your Label</div>` to define individual options when not using an `endpoint`.
    *   `data-value`: (Required) The actual value for the option.
    *   The text content of the `div` will be used as the display label.
    *   Other `data-*` attributes on the option `div` will be copied to the internal option object.

## Properties (JavaScript API)

*   `selected`: Getter/setter. For single select, gets/sets the selected option object or `null`. For multiple select, gets/sets an array of selected option objects or an empty array. When setting, you can provide a single value/object or an array of values/objects. The component tries to match by `value`, `id`, or `name` properties of the options.
*   `value`: Getter/setter. A simpler way to get/set the selected value(s). For single select, it's the `data-value` of the selected option. For multiple select, it's an array of `data-value`s.
*   `getValue()`: Returns the current selected value(s) (same as the `value` getter).
*   `setValue(valueOrValues)`: Sets the selection. For single mode, `valueOrValues` is a single value. For multiple mode, it's an array of values. Matches against `data-value` of options.
*   `clearSelection()`: Clears all selected items and updates the display.
*   `disabled`: (Boolean) Gets or sets the disabled state of the component.

## Events

*   `modern-select-change`: Fired when the selection changes due to user interaction or programmatic `clearSelection()`.
    *   `detail`: `{ selected: object | Array<object> | null }` - The selected option object(s) or null.
*   `modern-select-open`: Fired when the dropdown opens.
*   `modern-select-close`: Fired when the dropdown closes.
*   `modern-select-remote-error`: Fired if an error occurs while fetching data from the `endpoint`.
    *   `detail`: `{ error: Error }`

## Styling

Key parts of the component can be styled using CSS custom properties. Examples:

```css
modern-select {
  --modern-select-width: 300px;
  --modern-select-border-color: #ccc;
  --modern-select-border-focus-color: #007bff;
  --modern-select-background-color: #fff;
  --modern-select-text-color: #333;
  --modern-select-placeholder-color: #888;
  --modern-select-dropdown-background: #fff;
  --modern-select-dropdown-border-color: #ccc;
  --modern-select-option-hover-bg: #f0f0f0;
  --modern-select-option-selected-bg: #007bff;
  --modern-select-option-selected-text-color: #fff;
  --modern-select-tag-bg: #007bff;
  --modern-select-tag-text-color: #fff;
  --modern-select-tag-remove-button-hover-bg: #0056b3;
}
```

## Keyboard Interactions

### When the select display is focused:
*   **Enter, Space, ArrowDown, ArrowUp**: Opens the dropdown and focuses the search input (if multiple) or highlights the first/last option.

### When the dropdown is open and search input (or list) is focused:
*   **ArrowDown**: Moves focus/highlight to the next option. Cycles to the first if at the end.
*   **ArrowUp**: Moves focus/highlight to the previous option. Cycles to the last if at the beginning.
*   **Home**: Moves focus/highlight to the first visible option.
*   **End**: Moves focus/highlight to the last visible option.
*   **Enter**: Selects the highlighted option. If multiple, keeps dropdown open. If single, closes dropdown.
*   **Space**: (If on an option, not search input) Selects the highlighted option (primarily for single select or toggling in multiple if configured).
*   **Escape**: Closes the dropdown and reverts focus to the main select display.
*   **Tab**: Moves focus away from the component. If dropdown was open, it closes.
*   **Typing (in search input)**: Filters the options list.

This documentation provides a guide to using the `modern-select` component. For advanced use cases or specific behaviors, refer to the component's source code or more detailed examples.

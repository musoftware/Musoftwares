# Rule: Advanced Selection for Large Datasets (Clients/Users)

## Problem Statement
Standard HTML `<select>` elements, basic unpaginated dropdowns, and un-debounced select menus load all records into the DOM at once. For systems with large datasets (such as $10\text{K}+$ clients), this causes massive DOM performance degradation, high memory overhead, slow API response times, and an unusable user experience. 

---

## Rules & Guidelines

### 1. Simple Dropdown Prohibition
- **Never** use a standard `<select>` dropdown, simple Shadcn/UI `<Select>`, or unpaginated popovers for selecting entities that scale beyond $100$ records (e.g., Clients, Projects, Users).
- **Example of Failure**:
  ```tsx
  // ❌ INCORRECT (Loads all 10K clients in memory at once)
  <select value={selectedClient} onChange={handleChange}>
      {clients.map(client => (
          <option key={client.id} value={client.id}>{client.name}</option>
      ))}
  </select>
  ```

---

### 2. Mandatory Asynchronous Searchable Comboboxes
- Always use an asynchronous, search-on-type combobox or popover input (e.g. a custom Combobox/Autocomplete component powered by debounced state).
- The dropdown options must load dynamically from the backend as the user types.
- Pre-selected values (e.g., in "Edit Form" states) must be loaded and formatted correctly, mapping the existing record without forcing the load of the entire dataset.

---

### 3. Backend Search & Pagination Constraints
- The backend controller or API endpoint feeding the client selection must **never** return all clients at once (e.g. avoiding `$clients = Client::all()`).
- Always implement input query filtering (`q` or `search`) and paginate or limit the results to a small set (e.g., $15$ to $20$ items).
- **Example API Logic**:
  ```php
  // ✅ CORRECT (Filtered, paginated, lightweight)
  public function search(Request $request)
  {
      $search = $request->input('q');
      
      $clients = TenantClient::where('tenant_id', session('tenant_id'))
          ->when($search, function ($query, $search) {
              $query->where(function ($sub) use ($search) {
                  $sub->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
              });
          })
          ->limit(20)
          ->get(['id', 'name', 'email', 'currency_id']);
          
      return response()->json($clients);
  }
  ```

---

### 4. Frontend Debouncing & UX Patterns
- **Debounced Fetch**: Frontend search inputs must debounce network requests (waiting at least $300\text{ms}$ after the user stops typing) to prevent server overload.
- **Visual Feedback**: Display a loading spinner or skeleton loader while fetching results.
- **Refinement Indicators**: If the results are capped (e.g., 20 items), display a notice such as *"Showing top 20 matches. Type to refine..."*.

---

### 5. Summary Checklist
- [ ] Are dropdowns for entities with potential large records (e.g. Clients) asynchronously searched?
- [ ] Is server-side query input debounced on the frontend by at least $300\text{ms}$?
- [ ] Does the backend endpoint filter and limit/paginate the database results?
- [ ] Does the edit view correctly pre-load and display the selected item details without loading the full list?



---

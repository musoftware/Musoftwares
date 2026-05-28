import re
import sys

def main():
    file_path = r'd:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js\Pages\ERP\Dashboard.tsx'
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Generic function to replace buttons with Links
    def replace_button(content, state_setter, route_call):
        # Match <button onClick={() => setXXX(true)} ...> ... </button>
        pattern_button = r'<button[^>]*onClick=\{[^}]*' + state_setter + r'\([^}]*\}[^>]*>([\s\S]*?)</button>'
        content = re.sub(pattern_button, r'<Link href={' + route_call + r'} className="p-1.5 hover:bg-slate-100 rounded text-slate-400">\1</Link>', content)
        
        # Match <Button ... onClick={() => setXXX(true)} ...> ... </Button>
        pattern_Button = r'<Button[^>]*onClick=\{[^}]*' + state_setter + r'\([^}]*\}[^>]*>([\s\S]*?)</Button>'
        content = re.sub(pattern_Button, r'<Link href={' + route_call + r'}><Button size="sm" className="shadow-none">\1</Button></Link>', content)
        
        # Match <div ... onClick={() => setXXX(true)} ...> ... </div>
        pattern_div = r'<div[^>]*onClick=\{[^}]*' + state_setter + r'\([^}]*\}[^>]*>([\s\S]*?)</div>\s*</div>'
        # content = re.sub(pattern_div, r'<Link href={' + route_call + r'}>\1</Link>', content) # too risky to parse div

        return content

    content = replace_button(content, 'setShowWalletModal', 'route("erp.clients.wallet.adjust", selectedClient?.id)')
    content = replace_button(content, 'setShowAddExpenseModal', 'route("erp.expenses.create")')
    content = replace_button(content, 'setShowAddDocModal', 'route("erp.files.create")')
    content = replace_button(content, 'setShowAddContractModal', 'route("erp.contracts.create")')
    content = replace_button(content, 'setShowAddTicketModal', 'route("erp.tickets.create")')
    content = replace_button(content, 'setShowAddProviderModal', 'route("erp.storage-providers.create")')
    content = replace_button(content, 'setShowAddClientModal', 'route("erp.clients.create")')
    content = replace_button(content, 'setShowEditClientModal', 'route("erp.clients.edit", selectedClient?.id)')
    content = replace_button(content, 'setShowAddProjectModal', 'route("erp.projects.create")')

    # Remove all modal logic from the end of the file. 
    modal_section = r'\{\/\* ────────────────────────────────────────────────────────\s*MODALS AND OVERLAYS SECTION\s*──────────────────────────────────────────────────────── \*\/\}.*?</div>\s*</ERPLayout>'
    new_tail = '</div>\n        </ERPLayout>'
    content = re.sub(modal_section, new_tail, content, flags=re.DOTALL)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Done")

if __name__ == "__main__":
    main()

import sys

with open('d:\\APP\\tokopon-zed\\app\\Livewire\\Admin\\Reporting\\SalesReport.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

start_idx = -1
end_idx = -1

for i, line in enumerate(lines):
    if 'public function exportCsvOpsi3()' in line:
        start_idx = i
    if start_idx != -1 and 'public function render()' in line:
        end_idx = i
        break

with open('d:\\APP\\tokopon-zed\\app\\Livewire\\Admin\\Reporting\\new_export_3.php', 'r', encoding='utf-8') as f:
    new_method = f.read()

# Make sure we don't accidentally swallow extra newlines
# end_idx points to the line with `public function render()`.
# We want to replace from start_idx up to (but not including) the blank lines before render()
# actually, let's just go backwards from end_idx to find the end of the method
replace_end = end_idx - 1
while replace_end > start_idx and lines[replace_end].strip() == '':
    replace_end -= 1

if start_idx != -1 and end_idx != -1:
    with open('d:\\APP\\tokopon-zed\\app\\Livewire\\Admin\\Reporting\\SalesReport.php', 'w', encoding='utf-8') as f:
        f.writelines(lines[:start_idx])
        f.write(new_method)
        f.write("\n\n")
        f.writelines(lines[replace_end+1:])
    print("Replaced successfully!")
else:
    print(f"Could not find method boundaries! start: {start_idx}, end: {end_idx}")

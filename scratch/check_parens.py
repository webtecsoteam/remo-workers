import sys

def check_parens(filename):
    with open(filename, 'r') as f:
        content = f.read()
    
    stack = []
    in_string = None
    escaped = False
    
    for i, char in enumerate(content):
        if escaped:
            escaped = False
            continue
        if char == '\\':
            escaped = True
            continue
        
        if in_string:
            if char == in_string:
                in_string = None
            continue
        
        if char in ("'", '"', '`'):
            in_string = char
            continue
            
        if char == '(':
            stack.append(i)
        elif char == ')':
            if not stack:
                # Find line number
                line = content.count('\n', 0, i) + 1
                print(f"Error: Extra closing paren at line {line}")
                return
            stack.pop()
            
    if stack:
        for pos in stack:
            line = content.count('\n', 0, pos) + 1
            print(f"Error: Unclosed opening paren at line {line}")
    else:
        print("Parens are balanced")

check_parens('freelancer/includes/footer.php')

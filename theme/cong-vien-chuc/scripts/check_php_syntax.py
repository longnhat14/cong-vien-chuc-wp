import re

def check_brackets(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        code = f.read()

    # Stack for matching brackets
    stack = []
    line_no = 1
    col_no = 0
    in_string = False
    string_char = ''
    
    for i, char in enumerate(code):
        if char == '\n':
            line_no += 1
            col_no = 0
        else:
            col_no += 1

        if in_string:
            if char == string_char and code[i-1] != '\\':
                in_string = False
            continue
        
        if char in ('"', "'"):
            in_string = True
            string_char = char
            continue

        if char in ('(', '[', '{'):
            stack.append((char, line_no, col_no))
        elif char in (')', ']', '}'):
            if not stack:
                print(f"Unmatched closing bracket '{char}' at line {line_no}:{col_no}")
                return False
            top, t_line, t_col = stack.pop()
            match = {'(': ')', '[': ']', '{': '}'}[top]
            if char != match:
                print(f"Mismatch '{top}' from line {t_line}:{t_col} closed by '{char}' at line {line_no}:{col_no}")
                return False

    if stack:
        top, t_line, t_col = stack[-1]
        print(f"Unclosed '{top}' from line {t_line}:{t_col}")
        return False

    print(f"Brackets balanced in {filepath}")
    return True

check_brackets(r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-recruitments.php")
check_brackets(r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\inc\services\class-cvc-subpage-fixtures.php")

import re, sys, tinycss2

SRC = sys.argv[1]
CHROME = {'topline', 'inner', 'secondary', 'site-header', 'nav', 'brand', 'brand-mark', 'brand-name', 'brand-sub',
          'nav-links', 'nav-actions', 'nav-icon', 'nav-group', 'nav-sub', 'nav-parent', 'nav-caret', 'nav-badge',
          'menu-toggle', 'btn', 'btn-primary', 'btn-outline', 'btn-small', 'footer', 'footer-grid', 'footer-links',
          'footer-bottom', 'container', 'icon'}
STATE = {'open'}
ROOTS = ':root :is(.ch-topline,.ch-header,.ch-footer)'

css = open(SRC, encoding='utf-8').read()
rules = tinycss2.parse_stylesheet(css, skip_comments=True, skip_whitespace=True)
CLS = re.compile(r'\.([A-Za-z_][\w-]*)')
used_anim = set()
TABLET = []
keyframes = {}
varsdecl = ''


def keep_selector(sel):
    sel = sel.strip()
    classes = CLS.findall(sel)
    if not classes:
        return None
    if any(c not in CHROME and c not in STATE for c in classes):
        return None
    # chỉ chọn selector mà phần tử đầu là class chrome (không bắt đầu bằng thẻ chung như body/h1)
    first = re.split(r'[\s>+~]+', sel)[0]
    if not first.startswith('.'):
        return None
    out = CLS.sub(lambda m: '.' + (m.group(1) if m.group(1) in STATE else 'ch-' + m.group(1)), sel)
    out = out.replace('.ch-site-header', '.ch-header')
    return ':root ' + out


def do_rules(nodes):
    global varsdecl
    out = []
    for r in nodes:
        if r.type == 'qualified-rule':
            prelude = tinycss2.serialize(r.prelude).strip()
            body = tinycss2.serialize(r.content).strip()
            if prelude == ':root':
                varsdecl += body.rstrip().rstrip(';') + ';'
                continue
            sels = [keep_selector(s) for s in prelude.split(',')]
            sels = [s for s in sels if s]
            if not sels:
                continue
            for m in re.finditer(r'animation(?:-name)?\s*:\s*([\w-]+)', body):
                used_anim.add(m.group(1))
            out.append(','.join(sels) + '{' + body + '}')
        elif r.type == 'at-rule':
            kw = r.lower_at_keyword
            if kw == 'keyframes':
                keyframes[tinycss2.serialize(r.prelude).strip()] = '@keyframes ' + tinycss2.serialize(r.prelude).strip() + '{' + tinycss2.serialize(r.content) + '}'
            elif kw == 'media' and r.content is not None:
                pre = tinycss2.serialize(r.prelude)
                inner = do_rules(tinycss2.parse_rule_list(r.content, skip_comments=True, skip_whitespace=True))
                if inner:
                    out.append('@media' + pre + '{\n ' + '\n '.join(inner) + '\n}')
                if inner and re.sub(r'\s', '', pre) == '(max-width:760px)':
                    TABLET.extend(x for x in inner if re.search(r'ch-(nav-links|menu-toggle|nav-group|nav-sub|nav-caret|nav-actions|nav-icon)', x.split('{')[0]))
    return out


body = do_rules(rules)
print('/* Header + footer dùng chung toàn site (18/10/2026) - SINH TỰ ĐỘNG từ home-v17.css (genchrome.py):')
print(' * giữ nguyên giao diện trang chủ, đổi class sang tiền tố ch- để không va chạm Tailwind/cvc-doc/style.css. */')
print(ROOTS + '{' + varsdecl + '}')
for b in body:
    print(b)
print('/* Máy tính bảng (761-1100px): menu thu vào nút 3 gạch như di động (header có thêm tìm kiếm, thông báo). */')
print('@media(min-width:761px) and (max-width:1100px){\n ' + '\n '.join(TABLET) + '\n}')
for name in sorted(used_anim):
    if name in keyframes:
        print(keyframes[name])

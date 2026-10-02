import os

files_to_fix = [
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\header.php",
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-courses.php",
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-exams.php",
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-knowledge.php",
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-legal-documents.php",
    r"d:\Claude\Workspace\Account1\Frontend\cong-vien-chuc-wp\theme\cong-vien-chuc\template-topics.php",
]

# 1. Header logo
with open(files_to_fix[0], 'r', encoding='utf-8') as f:
    h_text = f.read()
h_text = h_text.replace('<a href="#" class="flex items-center space-x-3.5 group">', '<a href="<?php echo esc_url( home_url( \'/\' ) ); ?>" class="flex items-center space-x-3.5 group">')
with open(files_to_fix[0], 'w', encoding='utf-8') as f:
    f.write(h_text)

# 2. Courses
with open(files_to_fix[1], 'r', encoding='utf-8') as f:
    c_text = f.read()
c_text = c_text.replace('<a href="#" class="flex items-center', '<a href="<?php echo esc_url( cvc_courses_url() ); ?>" class="flex items-center')
with open(files_to_fix[1], 'w', encoding='utf-8') as f:
    f.write(c_text)

# 3. Exams
with open(files_to_fix[2], 'r', encoding='utf-8') as f:
    e_text = f.read()
e_text = e_text.replace('<a href="#" class="flex items-center', '<a href="<?php echo esc_url( cvc_exams_url() ); ?>" class="flex items-center')
with open(files_to_fix[2], 'w', encoding='utf-8') as f:
    f.write(e_text)

# 4. Knowledge
with open(files_to_fix[3], 'r', encoding='utf-8') as f:
    k_text = f.read()
k_text = k_text.replace('<a href="#" class="flex items-center', '<a href="<?php echo esc_url( cvc_knowledge_url() ); ?>" class="flex items-center')
with open(files_to_fix[3], 'w', encoding='utf-8') as f:
    f.write(k_text)

# 5. Legal docs
with open(files_to_fix[4], 'r', encoding='utf-8') as f:
    l_text = f.read()
l_text = l_text.replace('<a href="#" class="flex items-center', '<a href="<?php echo esc_url( cvc_legal_documents_url() ); ?>" class="flex items-center')
with open(files_to_fix[4], 'w', encoding='utf-8') as f:
    f.write(l_text)

# 6. Topics
with open(files_to_fix[5], 'r', encoding='utf-8') as f:
    t_text = f.read()
t_text = t_text.replace('<a href="#" class="flex items-center', '<a href="<?php echo esc_url( cvc_topics_url() ); ?>" class="flex items-center')
with open(files_to_fix[5], 'w', encoding='utf-8') as f:
    f.write(t_text)

print("All remaining hash links fixed!")

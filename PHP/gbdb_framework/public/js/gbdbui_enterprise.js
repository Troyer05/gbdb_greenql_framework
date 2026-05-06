(function () {
    'use strict';

    const textarea = document.getElementById('enterprise-pattern-json');
    const title = document.getElementById('enterprise-editor-title');
    const formatBtn = document.getElementById('enterprise-format-json');
    const resetBtn = document.getElementById('enterprise-reset-template');
    const template = String(window.GBDB_ENTERPRISE_PATTERN_TEMPLATE || '{}');
    const patterns = window.GBDB_ENTERPRISE_PATTERNS || {};

    if (!textarea) return;

    const dispatchInput = () => textarea.dispatchEvent(new Event('input', { bubbles: true }));

    const setEditor = (value, label) => {
        textarea.value = value;
        if (title) title.textContent = label || 'Pattern Editor';
        dispatchInput();
        textarea.focus();
    };

    const formatJson = () => {
        try {
            const parsed = JSON.parse(textarea.value || '{}');
            setEditor(JSON.stringify(parsed, null, 4), title ? title.textContent : 'Pattern Editor');
        } catch (e) {
            textarea.classList.add('is-invalid-json');
            window.setTimeout(() => textarea.classList.remove('is-invalid-json'), 900);
        }
    };

    document.querySelectorAll('.enterprise-edit-pattern').forEach(button => {
        button.addEventListener('click', () => {
            const name = String(button.dataset.pattern || '');
            if (!name || !patterns[name]) return;
            setEditor(String(patterns[name]), 'Bearbeiten: ' + name + '.json');
        });
    });

    if (formatBtn) formatBtn.addEventListener('click', formatJson);
    if (resetBtn) resetBtn.addEventListener('click', () => setEditor(template, 'Template: new_pattern'));

    textarea.addEventListener('blur', () => {
        try {
            JSON.parse(textarea.value || '{}');
            textarea.classList.remove('is-invalid-json');
        } catch (e) {
            textarea.classList.add('is-invalid-json');
        }
    });
}());

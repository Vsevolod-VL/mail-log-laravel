import Alpine from 'alpinejs'

const themeKey = 'mail-log:theme'

function applyTheme(theme) {
    const root = document.documentElement
    if (theme === 'dark') {
        root.classList.add('dark')
    } else {
        root.classList.remove('dark')
    }
}

function preferredTheme() {
    const stored = localStorage.getItem(themeKey)
    if (stored === 'dark' || stored === 'light') {
        return stored
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

applyTheme(preferredTheme())

Alpine.data('mailLogShell', () => ({
    theme: preferredTheme(),
    init() {
        applyTheme(this.theme)
    },
    toggleTheme() {
        this.theme = this.theme === 'dark' ? 'light' : 'dark'
        localStorage.setItem(themeKey, this.theme)
        applyTheme(this.theme)
    },
}))

Alpine.data('bodyPreview', (html, text) => ({
    mode: 'html',
    html,
    text,
    setMode(mode) {
        this.mode = mode
        this.$nextTick(() => this.render())
    },
    init() {
        this.render()
    },
    render() {
        const frame = this.$refs.frame
        if (!frame) {
            return
        }
        if (this.mode === 'html') {
            frame.srcdoc = this.html || '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;color:#888;padding:24px"><em>No HTML body recorded.</em></body>'
        } else if (this.mode === 'text') {
            const escaped = (this.text || 'No text body recorded.')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
            frame.srcdoc = `<!doctype html><meta charset="utf-8"><pre style="font-family:ui-monospace,monospace;white-space:pre-wrap;padding:24px;margin:0;font-size:13px;line-height:1.6;color:#27272a">${escaped}</pre>`
        } else {
            const escaped = (this.html || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
            frame.srcdoc = `<!doctype html><meta charset="utf-8"><pre style="font-family:ui-monospace,monospace;white-space:pre-wrap;padding:24px;margin:0;font-size:12px;line-height:1.55;color:#52525b">${escaped}</pre>`
        }
    },
}))

Alpine.data('copyButton', (value) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(value)
            this.copied = true
            setTimeout(() => (this.copied = false), 1200)
        } catch (_e) {
            this.copied = false
        }
    },
}))

Alpine.data('sendsTable', () => ({
    expanded: null,
    toggle(id) {
        this.expanded = this.expanded === id ? null : id
    },
    isOpen(id) {
        return this.expanded === id
    },
}))

Alpine.data('testSendModal', () => ({
    open: false,
    show() {
        this.open = true
        this.$nextTick(() => this.$refs.email?.focus())
    },
    hide() {
        this.open = false
    },
}))

window.Alpine = Alpine
Alpine.start()

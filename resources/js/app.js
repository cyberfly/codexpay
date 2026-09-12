import Chart from 'chart.js/auto';

window.Chart = Chart;

window.productDescriptionEditor = (value) => {
    let editor = null;

    return {
        value,
        isReady: false,

        async init() {
            const [{ Editor }, { default: StarterKit }] = await Promise.all([
                import('@tiptap/core'),
                import('@tiptap/starter-kit'),
            ]);

            editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: {
                            levels: [2, 3, 4],
                        },
                        link: false,
                    }),
                ],
                content: this.value || '',
                editorProps: {
                    attributes: {
                        class: 'min-h-48 px-3 py-2 text-sm leading-6 text-zinc-900 outline-none dark:text-zinc-100',
                    },
                },
                onUpdate: ({ editor }) => {
                    this.value = editor.getHTML();
                },
            });

            this.isReady = true;
        },

        isActive(name, attributes = {}) {
            return editor?.isActive(name, attributes) ?? false;
        },

        run(command, attributes = null) {
            const chain = editor?.chain().focus();

            if (chain === undefined) {
                return;
            }

            if (attributes === null) {
                chain[command]().run();

                return;
            }

            chain[command](attributes).run();
        },

        setContent(description) {
            this.value = description || '';

            if (editor !== null && this.value !== editor.getHTML()) {
                editor.commands.setContent(this.value, { emitUpdate: false });
            }
        },
    };
};

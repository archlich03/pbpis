import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

export default function tiptapEditor(content = '', fieldName = 'summary') {
    return {
        editor: null,
        content: content,
        fieldName: fieldName,
        
        init() {
            // Prevent double initialization
            if (this.editor) {
                return;
            }
            
            // Initialize hidden input with initial content
            if (this.$refs.hiddenInput) {
                this.$refs.hiddenInput.value = this.content;
            }
            
            // Clear the editor div before initializing
            if (this.$refs.editor) {
                this.$refs.editor.innerHTML = '';
            }
            
            this.editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: {
                            levels: [1, 2, 3]
                        },
                        bulletList: {
                            keepMarks: true,
                            keepAttributes: false,
                        },
                        orderedList: {
                            keepMarks: true,
                            keepAttributes: false,
                        },
                    })
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: 'prose prose-sm dark:prose-invert max-w-none focus:outline-none min-h-[150px] p-3 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md'
                    }
                },
                onUpdate: ({ editor }) => {
                    this.content = editor.getHTML();
                    // Update hidden input
                    if (this.$refs.hiddenInput) {
                        this.$refs.hiddenInput.value = this.content;
                    }
                }
            });
        },
        
        destroy() {
            if (this.editor) {
                this.editor.destroy();
            }
        },
        
        toggleBold() {
            this.editor.chain().focus().toggleBold().run();
        },
        
        toggleItalic() {
            this.editor.chain().focus().toggleItalic().run();
        },
        
        toggleBulletList() {
            this.editor.chain().focus().toggleBulletList().run();
        },
        
        toggleOrderedList() {
            this.editor.chain().focus().toggleOrderedList().run();
        },
        
        setHeading(level) {
            if (level === 0) {
                this.editor.chain().focus().setParagraph().run();
            } else {
                this.editor.chain().focus().toggleHeading({ level }).run();
            }
        },
        
        toggleBlockquote() {
            this.editor.chain().focus().toggleBlockquote().run();
        },
        
        setHorizontalRule() {
            this.editor.chain().focus().setHorizontalRule().run();
        },
        
        undo() {
            this.editor.chain().focus().undo().run();
        },
        
        redo() {
            this.editor.chain().focus().redo().run();
        },
        
        isActive(type, attrs = {}) {
            if (!this.editor) return false;
            return this.editor.isActive(type, attrs);
        }
    };
}

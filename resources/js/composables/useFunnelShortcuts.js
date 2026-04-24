/**
 * useFunnelShortcuts - keyboard shortcuts for the Telegram Funnel Builder.
 *
 * Registers a window keydown listener and calls back into the builder
 * with semantic action names. Integrated by TelegramFunnelBuilder.vue.
 *
 * Shortcuts:
 *   ?                   -> 'help'
 *   Delete / Backspace  -> 'delete'
 *   Ctrl/Cmd+S          -> 'save'
 *   Ctrl+D              -> 'duplicate'
 *   Ctrl+Z              -> 'undo'
 *   Ctrl+Shift+Z        -> 'redo'
 *   Esc                 -> 'escape'
 */
import { onBeforeUnmount, onMounted } from 'vue';

function isEditableTarget(e) {
    const el = e.target;
    if (!el) return false;
    const tag = (el.tagName || '').toUpperCase();
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true;
    if (el.isContentEditable) return true;
    return false;
}

export function useFunnelShortcuts(handler) {
    const onKeyDown = (e) => {
        const ctrl = e.ctrlKey || e.metaKey;

        // Esc always active (even in inputs, to close modals)
        if (e.key === 'Escape') {
            handler && handler('escape', e);
            return;
        }

        // Ctrl+S always (even in inputs) — saves are universal
        if (ctrl && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            handler && handler('save', e);
            return;
        }

        // Do not fire editing-destructive shortcuts inside input fields
        if (isEditableTarget(e)) return;

        if (e.key === '?' || (e.shiftKey && e.key === '/')) {
            e.preventDefault();
            handler && handler('help', e);
            return;
        }

        if (e.key === 'Delete' || e.key === 'Backspace') {
            // Avoid intercepting backspace when not editing — but this branch
            // only runs when NOT editable, so it's safe.
            e.preventDefault();
            handler && handler('delete', e);
            return;
        }

        if (ctrl && (e.key === 'd' || e.key === 'D')) {
            e.preventDefault();
            handler && handler('duplicate', e);
            return;
        }

        if (ctrl && e.shiftKey && (e.key === 'z' || e.key === 'Z')) {
            e.preventDefault();
            handler && handler('redo', e);
            return;
        }

        if (ctrl && (e.key === 'z' || e.key === 'Z')) {
            e.preventDefault();
            handler && handler('undo', e);
            return;
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', onKeyDown);
    });
    onBeforeUnmount(() => {
        window.removeEventListener('keydown', onKeyDown);
    });
}

export default useFunnelShortcuts;

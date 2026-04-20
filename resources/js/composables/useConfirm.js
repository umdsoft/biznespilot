import { ref, reactive } from 'vue';

const state = reactive({
  show: false,
  title: '',
  message: '',
  confirmText: 'Tasdiqlash',
  cancelText: 'Bekor qilish',
  type: 'danger', // danger, warning, info
  loading: false,
  resolve: null,
});

export function useConfirm() {
  const confirm = (options = {}) => {
    if (typeof options === 'string') {
      options = { message: options };
    }

    state.title = options.title || 'Tasdiqlang';
    state.message = options.message || 'Davom etishni xohlaysizmi?';
    state.confirmText = options.confirmText || 'Tasdiqlash';
    state.cancelText = options.cancelText || 'Bekor qilish';
    state.type = options.type || 'danger';
    state.loading = false;
    state.show = true;

    return new Promise((resolve) => {
      state.resolve = resolve;
    });
  };

  const handleConfirm = () => {
    state.show = false;
    if (state.resolve) state.resolve(true);
  };

  const handleCancel = () => {
    state.show = false;
    if (state.resolve) state.resolve(false);
  };

  return {
    state,
    confirm,
    handleConfirm,
    handleCancel,
  };
}

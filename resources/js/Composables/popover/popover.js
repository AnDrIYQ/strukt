import { ref } from 'vue';

export default function usePopover() {
    const visibleState = ref(false);

    const close = () => {
        visibleState.value = false;
    };

    const open = () => {
        visibleState.value = true;
    }

    const toggle = () => {
        visibleState.value = !visibleState.value;
    };

    return {
        opened: visibleState,
        open,
        close,
        toggle
    };
};

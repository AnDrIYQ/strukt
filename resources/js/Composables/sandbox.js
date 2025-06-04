import { computed } from 'vue';
import { userSandboxStore } from "@/Store/sandbox";

export default function useSandbox(checkOnInit = true) {
    const sandbox = userSandboxStore();

    return {
        loading: computed(() => sandbox.loading),
        strukt: sandbox.strukt,
        checkMe: sandbox.checkMe,
        me: computed(() => sandbox.me),
    };
}

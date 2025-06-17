import { defineStore } from 'pinia';
import Strukt from "@matt_kensington/strukt";

export const userSandboxStore = defineStore('sandbox', {
    state: () => ({
        me: false,
        loading: true,
        strukt: new Strukt({
            apiUrl: 'http://31.131.17.191',
            wsHost: '31.131.17.191',
            wsKey: 'ldr1ppkztyk4egoxwej8',
            wsPort: 8080
        }),
    }),
    actions: {
        async checkMe() {
            this.loading = true;
            try {
                this.me = await this.strukt.me();
            } catch ({ response }) {
                if (response.status === 401) {
                    this.me = null;
                }
            } finally {
                this.loading = false;
            }
        },
    },
});

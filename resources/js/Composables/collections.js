import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

export default function useCollections() {
    const collectionOptions = computed(() => {
        return Object.keys(page.props.collectionsForSidebar).map((collection) => ({
            value: page.props.collectionsForSidebar[collection].name,
            label: page.props.collectionsForSidebar[collection].label,
        }));
    });

    return {
        options: collectionOptions,
    };
}

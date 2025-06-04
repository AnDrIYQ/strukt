import { mount } from '@vue/test-utils'
import DataTable from '@/Components/DataTable.vue'

describe('DataTable', () => {
    const factory = (props = {}, slots = {}) => {
        return mount(DataTable, {
            props: {
                items: [
                    { id: 1, name: 'Alpha', email: 'a@example.com' },
                    { id: 2, name: 'Bravo', email: 'b@example.com' },
                ],
                columns: [
                    { id: 'name', header: 'Name' },
                    { id: 'email', header: 'Email' },
                ],
                links: [],
                ...props
            },
            slots
        });
    };

    it('renders headers and rows', () => {
        const wrapper = factory();
        expect(wrapper.text()).toContain('Name');
        expect(wrapper.text()).toContain('Email');
        expect(wrapper.text()).toContain('Alpha');
        expect(wrapper.text()).toContain('Bravo');
    });

    it('sorts by column when clicked', async () => {
        const wrapper = factory();
        const headers = wrapper.findAll('div.cursor-pointer');
        await headers[0].trigger('click');
        expect(wrapper.html()).toContain('↑');

        await headers[0].trigger('click');
        expect(wrapper.html()).toContain('↓');
    });

    it('toggles row selection', async () => {
        const wrapper = factory();

        const [headerCheckbox, row1Checkbox, row2Checkbox] = wrapper.findAll('input[type="checkbox"]');

        await row1Checkbox.setValue(true);
        expect(wrapper.vm.selected).toEqual([1]);

        await row2Checkbox.setValue(true);
        expect(wrapper.vm.selected).toEqual([1, 2]);

        await row1Checkbox.setValue(false);
        expect(wrapper.vm.selected).toEqual([2]);
    });

    it('emits search event', async () => {
        const wrapper = factory();
        const input = wrapper.get('input[type="text"]');
        await input.setValue('Test');
        await input.trigger('input');
        expect(wrapper.emitted().search[0]).toEqual(['Test']);
    });

    it('renders actions slot', () => {
        const wrapper = factory({}, {
            actions: '<template #actions="{ row }"><span>Action {{ row.id }}</span></template>',
        });

        expect(wrapper.html()).toContain('Action 1');
        expect(wrapper.html()).toContain('Action 2');
    });

    it('renders empty state when no items', () => {
        const wrapper = factory({ items: [] });
        expect(wrapper.text()).toContain('Даних не знайдено');
    });
});

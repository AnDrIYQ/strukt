import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import XRowsInput from '@/Components/inputs/XRowsInput.vue'

describe('XRowsInput', () => {
    function mountComponent(props = {}, slots = {}) {
        return mount(XRowsInput, {
            props: {
                modelValue: [],
                columns: ['name'],
                ...props
            },
            slots: {
                name: ({ modelValue, 'onUpdate:modelValue': update }) => `
                <input data-testid="name-input" value="${modelValue || ''}" onInput="(${update.toString()})(event.target.value)" />
              `,
                ...slots
            }
        })
    }

    it('renders one empty row by default', () => {
        const wrapper = mountComponent()

        const removeButtons = wrapper.findAll('[data-testid="row"]')
        expect(removeButtons.length).toBe(1)
    })

    it('adds a new row when "add row" button is clicked', async () => {
        const wrapper = mountComponent();

        expect(wrapper.findAll('[data-testid="row"]').length).toBe(1);

        await wrapper.get('[data-testid="add-row"]').trigger('click');

        expect(wrapper.findAll('[data-testid="row"]').length).toBe(2);
    });

    it('removes a row and adds a blank one if none left', async () => {
        const wrapper = mountComponent({
            modelValue: [{ col1: 'value 1' }]
        });

        expect(wrapper.findAll('[data-testid="row"]').length).toBe(1);

        await wrapper.get('[data-testid="remove-row-0"]').trigger('click');

        const rows = wrapper.findAll('[data-testid="row"]');
        expect(rows.length).toBe(1);
        expect(wrapper.emitted()['update:modelValue']).toEqual([[[{}]]]);
    });

    it('moves a row up and down correctly', async () => {
        const wrapper = mountComponent({
            modelValue: [
                { col1: 'first' },
                { col1: 'second' }
            ]
        });

        await wrapper.get('[data-testid="move-up-1"]').trigger('click');
        expect(wrapper.emitted()['update:modelValue'].at(-1)).toEqual([[
            { col1: 'second' },
            { col1: 'first' }
        ]]);

        await wrapper.get('[data-testid="move-down-0"]').trigger('click');
        expect(wrapper.emitted()['update:modelValue'].at(-1)).toEqual([[
            { col1: 'first' },
            { col1: 'second' }
        ]]);
    });

    it('reacts to external modelValue changes', async () => {
        const wrapper = mountComponent({
            modelValue: [{ col1: 'initial' }]
        });

        expect(wrapper.findAll('[data-testid="row"]').length).toBe(1);

        await wrapper.setProps({
            modelValue: [
                { col1: 'changed 1' },
                { col1: 'changed 2' }
            ]
        });

        const rows = wrapper.findAll('[data-testid="row"]');
        expect(rows.length).toBe(2);
    });
})

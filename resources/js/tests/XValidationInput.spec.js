import { mount } from '@vue/test-utils'
import RulesInput from '@/Components/inputs/XValidationInput.vue'
import XSelect from '@/Components/inputs/XSelect.vue'
import TextInput from '@/Components/inputs/TextInput.vue'
import Button from '@/Components/Button.vue'
import { nextTick } from 'vue'

describe('XValidationInput', () => {
    const mountComponent = (options = {}) => {
        return mount(RulesInput, {
            props: {
                modelValue: '',
                placeholder: 'Select rules',
                type: { value: 'value' },
                ...options.props,
            },
            global: {
                stubs: {
                    TextInput: {
                        template: `<input data-testid="text-input" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" />`,
                        props: ['modelValue'],
                    },
                    Button: {
                        template: `<button data-testid="add-button" @click="$emit('click')"><slot /></button>`,
                    },
                    XSelect: {
                        name: 'x-select',
                        template: `
                          <select data-testid="rule-select" @change="$emit('update:modelValue', JSON.parse($event.target.value))">
                            <option :value="JSON.stringify({ value: 'min', label: 'Min', result: (p) => 'min:' + p, types: ['value'] })">min</option>
                          </select>
                        `,
                        props: ['modelValue', 'options'],
                    }
                },
            },
        })
    }

    it('opens popover on click', async () => {
        const wrapper = mountComponent()
        expect(wrapper.find('.popover').exists()).toBe(false)

        await wrapper.find('.input-field').trigger('click')
        expect(wrapper.find('.popover').exists()).toBe(true)
    })

    it('adds min rule with parameter', async () => {
        const wrapper = mountComponent()

        const element = wrapper.find('.input-field');
        await element.trigger('click');

        const selectRule = wrapper.findComponent({ name: 'x-select' });
        await selectRule.vm.$emit('update:modelValue', { value: 'required' });

        await wrapper.vm.$nextTick();

        const addButton =  wrapper.find('[data-testid="add-button"]');
        await addButton.trigger('click');

        expect(wrapper.emitted()['update:modelValue']).toBeTruthy()
        expect(wrapper.emitted()['update:modelValue'][1]).toEqual(['required'])
    })
})

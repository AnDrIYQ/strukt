import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import FieldErrors from '@/Components/FormErrors.vue';

describe('FieldErrors', () => {
    it('renders errors for simple field', () => {
        const wrapper = mount(FieldErrors, {
            props: {
                items: {
                    title: ['Поле обовʼязкове']
                },
                field: 'title'
            }
        });

        expect(wrapper.text()).toContain('Поле обовʼязкове.');
    });

    it('renders errors for indexed field with subfield', () => {
        const wrapper = mount(FieldErrors, {
            props: {
                items: {
                    'fields.0.name': ['Це обовʼязково']
                },
                field: 'fields',
                index: 0,
                subfield: 'name'
            }
        });

        expect(wrapper.text()).toContain('Це обовʼязково.');
    });

    it('renders nothing if no errors exist', () => {
        const wrapper = mount(FieldErrors, {
            props: {
                items: {},
                field: 'title'
            }
        });

        expect(wrapper.text()).toBe('');
    });

    it('supports multiple messages', () => {
        const wrapper = mount(FieldErrors, {
            props: {
                items: {
                    title: ['Перше', 'Друге']
                },
                field: 'title'
            }
        });

        const spans = wrapper.findAll('span');
        expect(spans.length).toBe(2);
        expect(wrapper.text()).toContain('Перше.');
        expect(wrapper.text()).toContain('Друге.');
    });
});

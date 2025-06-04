import { mount } from '@vue/test-utils'
import FormLayout from '@/Layouts/FormLayout.vue'
import { nextTick } from 'vue'

vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    const post = vi.fn((url, config) => {
        if (config?.onSuccess) config.onSuccess();
    });

    return {
        ...actual,
        usePage: () => ({
            props: {
                flash: {
                    success: 'OK!',
                },
            },
        }),
        useForm: (initial) => {
            const form = {
                ...initial,
                data: () => initial,
                hasErrors: false,
                errors: {},
                post,
                transform(cb) {
                    cb(initial);
                    return form;
                },
            };
            return form;
        },
    };
});

describe('FormLayout', () => {
    const factory = (props = {}, slots = {}) => {
        return mount(FormLayout, {
            props: {
                modelValue: { name: 'Test' },
                action: '/test-route',
                ...props
            },
            slots,
            global: {
                mocks: {
                    $page: {
                        props: {
                            flash: {},
                        }
                    }
                },
                stubs: ['Link'],
            }
        })
    }

    it('calls beforeSubmit and emits success on submit', async () => {
        const beforeSubmit = vi.fn((data) => {
            data.name = data.name.toUpperCase()
            return data
        })

        const wrapper = factory({ beforeSubmit }, {
            default: '<div>FORM CONTENT</div>',
        })

        wrapper.vm.form.submit = vi.fn((method, config) => {
            config.onSuccess()
        })

        await wrapper.find('form').trigger('submit.prevent')

        expect(beforeSubmit).toHaveBeenCalled()
        expect(wrapper.emitted('success')).toBeTruthy()
    })

    it('cleans empty values when trimEmpty is true', async () => {
        const wrapper = factory({
            modelValue: {
                name: '',
                age: 30,
                file: new File([], ''),
            },
            trimEmpty: true,
        })

        wrapper.vm.form.submit = vi.fn((method, config) => {
            const cleaned = wrapper.vm.clean(wrapper.vm.form.data())
            expect(cleaned).toEqual({ age: 30 })
            config.onSuccess()
        })

        await wrapper.find('form').trigger('submit.prevent')
    })

    it('shows flash message if available', async () => {
        const wrapper = mount(FormLayout, {
            props: {
                modelValue: { name: 'test' },
                action: '/test',
            },
            global: {
                mocks: {
                    $page: {
                        props: {
                            flash: {
                                success: 'OK!'
                            }
                        }
                    }
                },
                stubs: ['Link'],
            }
        })

        expect(wrapper.text()).toContain('OK!')
    })
})

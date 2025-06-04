import { mount } from '@vue/test-utils';
import AuthLayout from '@/Layouts/AuthLayout.vue';

describe('AuthLayout', () => {
    it('renders slots correctly', () => {
        const wrapper = mount(AuthLayout, {
            slots: {
                title: '<span data-testid="title">Login</span>',
                subtitle: '<span data-testid="subtitle">Welcome back!</span>',
                default: '<form data-testid="form">FORM</form>',
            },
            global: {
                stubs: ['Logo']
            }
        })

        expect(wrapper.get('[data-testid="title"]').text()).toBe('Login')
        expect(wrapper.get('[data-testid="subtitle"]').text()).toBe('Welcome back!')
        expect(wrapper.get('[data-testid="form"]').exists()).toBe(true)
    })

    it('renders footer with current year', () => {
        const wrapper = mount(AuthLayout, {
            global: {
                stubs: ['Logo']
            }
        })

        const year = new Date().getFullYear()
        expect(wrapper.text()).toContain(`© ${year} Strukt. Усі права захищені.`)
    })
})

import { mount } from '@vue/test-utils'
import AdminLayout from '@/Layouts/AdminLayout.vue'

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        props: {
            auth: {
                user: {
                    role: 'admin',
                    name: 'Admin Test',
                    updated_at: '2025-04-12T10:00:00Z',
                },
            },
            collectionsForSidebar: [
                { name: 'posts', label: 'Пости', icon: 'book', singleton: false },
                { name: 'about', label: 'Про нас', icon: 'info', singleton: true },
            ],
        },
    }),
    router: {
        post: vi.fn()
    }
}))

describe('AdminLayout', () => {
    const mountComponent = (overrides = {}) => {
        return mount(AdminLayout, {
            slots: {
                title: 'Dashboard',
                subtitle: 'Welcome to admin',
                default: '<div data-testid="main-content">Main Content</div>',
            },
            global: {
                stubs: {
                    Logo: { template: '<div>Logo</div>' },
                    Menu: true,
                    LogOut: true,
                    House: true,
                    BadgePlus: true,
                    Waypoints: true,
                    Languages: true,
                    UserPen: true,
                },
                mocks: {
                    $page: {
                        props: {
                            auth: {
                                user: {
                                    role: 'admin',
                                    name: 'Admin Name',
                                    updated_at: '2025-04-12T10:00:00Z'
                                }
                            },
                            collectionsForSidebar: [
                                {
                                    name: 'posts',
                                    label: 'Пости',
                                    icon: 'book',
                                    singleton: false
                                },
                                {
                                    name: 'about',
                                    label: 'Про нас',
                                    icon: 'info',
                                    singleton: true
                                }
                            ]
                        }
                    }
                },
                provide: {
                    page: {
                        props: {
                            auth: {
                                user: {
                                    role: 'admin',
                                    name: 'Admin Name',
                                    updated_at: '2025-04-12T10:00:00Z'
                                }
                            },
                            collectionsForSidebar: [
                                {
                                    name: 'posts',
                                    label: 'Пости',
                                    icon: 'book',
                                    singleton: false
                                },
                                {
                                    name: 'about',
                                    label: 'Про нас',
                                    icon: 'info',
                                    singleton: true
                                }
                            ]
                        }
                    }
                }
            },
            ...overrides
        })
    }

    it('renders layout and sidebar items for admin', async () => {
        const wrapper = mountComponent()
        expect(wrapper.html()).toContain('Strukt')
        expect(wrapper.html()).toContain('Головна')
        expect(wrapper.html()).toContain('Створити')
        expect(wrapper.html()).toContain('Колекції')
        expect(wrapper.html()).toContain('Пости')
        expect(wrapper.html()).toContain('Про нас (S)')
        expect(wrapper.find('[data-testid="main-content"]').exists()).toBe(true)
    })
})

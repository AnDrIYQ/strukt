import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import FileInput from '@/Components/inputs/FileInput.vue';
import { nextTick } from 'vue';

describe('FileInput', () => {
    global.URL.createObjectURL = vi.fn(() => 'blob:http://localhost/fake-preview-url');

    function mountComponent(props = {}) {
        return mount(FileInput, {
            props: {
                modelValue: null,
                label: 'Upload file',
                ...props,
            },
        });
    }

    it('shows preview if existing image is provided', async () => {
        const wrapper = mountComponent({ existing: 'https://example.com/image.jpg' });
        await nextTick();

        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('https://example.com/image.jpg');
    });

    it('emits image file and sets preview when uploading image', async () => {
        const wrapper = mountComponent();
        const input = wrapper.get('input[type="file"]');

        const file = new File(['image-content'], 'photo.jpg', { type: 'image/jpeg' });

        // створюємо кастомний change event з files
        const event = new Event('change');
        Object.defineProperty(event, 'target', {
            writable: false,
            value: { files: [file] }
        });

        await input.element.dispatchEvent(event);
        await nextTick();

        const emitted = wrapper.emitted()['update:modelValue'];
        expect(emitted).toBeTruthy();
        expect(emitted[0][0]).toBeInstanceOf(File);

        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
    });

    it('emits file and does not set preview for non-image file', async () => {
        const wrapper = mountComponent();
        const input = wrapper.get('input[type="file"]');

        const file = new File(['pdf-content'], 'file.pdf', { type: 'application/pdf' });

        const event = new Event('change');
        Object.defineProperty(event, 'target', {
            writable: false,
            value: { files: [file] }
        });

        await input.element.dispatchEvent(event);
        await nextTick();

        const emitted = wrapper.emitted()['update:modelValue'];
        expect(emitted).toBeTruthy();
        expect(emitted[0][0]).toBeInstanceOf(File);

        const img = wrapper.find('img');
        expect(img.exists()).toBe(false);
    });

    it('clears preview if image load fails', async () => {
        const wrapper = mountComponent({ existing: 'https://example.com/image.jpg' });
        await nextTick();

        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);

        await img.trigger('error');
        await nextTick();

        expect(wrapper.find('img').exists()).toBe(false);
    });
});

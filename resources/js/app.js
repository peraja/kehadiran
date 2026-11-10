window.loadHeic2any = async () => {
    if (!window.heic2any) {
        const module = await import('heic2any');
        window.heic2any = module.default || module;
    }
    return window.heic2any;
};

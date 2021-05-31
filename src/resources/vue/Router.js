
const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

const routes = [

    {
        path: '/credit-notes',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Credit Notes',
            metaTags: [
                {
                    name: 'description',
                    content: 'Credit Notes'
                },
                {
                    property: 'og:description',
                    content: 'Credit Notes'
                }
            ]
        }
    },
    {
        path: '/credit-notes/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Credit Note :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Credit Note'
                },
                {
                    property: 'og:description',
                    content: 'Create Credit Note'
                }
            ]
        }
    },
    {
        path: '/credit-notes/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Accounting :: Sales :: Credit Note',
            metaTags: [
                {
                    name: 'description',
                    content: 'Credit Note'
                },
                {
                    property: 'og:description',
                    content: 'Credit Note'
                }
            ]
        }
    },
    {
        path: '/credit-notes/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Credit Note :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Credit Note'
                },
                {
                    property: 'og:description',
                    content: 'Copy Credit Note'
                }
            ]
        }
    },
    {
        path: '/credit-notes/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Accounting :: Sales :: Credit Note :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Credit Note'
                },
                {
                    property: 'og:description',
                    content: 'Edit Credit Note'
                }
            ]
        }
    }

]

export default routes

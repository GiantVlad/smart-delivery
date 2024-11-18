import {
  mdiMonitor,
  mdiLock,
  mdiAlertCircle,
  mdiSquareEditOutline,
  mdiTable,
  mdiViewList,
  mdiTelevisionGuide,
  mdiResponsive,
  mdiPalette,
  mdiRoutes
} from '@mdi/js'

export default [
  {
    to: '/dashboard',
    icon: mdiMonitor,
    label: 'Dashboard'
  },
  {
    to: '/orders',
    label: 'Orders',
    icon: mdiTable
  },
  {
    to: '/tasks',
    label: 'Tasks',
    icon: mdiSquareEditOutline
  },
  {
    to: '/order',
    label: 'Create order',
    icon: mdiTelevisionGuide
  },
  {
    to: '/task',
    label: 'Create task',
    icon: mdiResponsive
  },
  {
    to: '/update-order-in-task',
    label: 'Update order in task',
    icon: mdiPalette
  },
  {
    to: '/edit-route',
    label: 'Edit Route',
    icon: mdiRoutes
  },
  {
    to: '/login',
    label: 'Login',
    icon: mdiLock
  },
  {
    to: '/error',
    label: 'Error',
    icon: mdiAlertCircle
  },
  {
    label: 'Dropdown',
    icon: mdiViewList,
    menu: [
      {
        label: 'Item One'
      },
      {
        label: 'Item Two'
      }
    ]
  },
]

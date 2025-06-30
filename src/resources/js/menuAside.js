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
  mdiRoutes, mdiTruckDelivery, mdiNaturePeople
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
    to: '/couriers',
    label: 'Couriers',
    icon: mdiTruckDelivery
  },
  {
    to: '/users',
    label: 'Users',
    icon: mdiNaturePeople
  },
]

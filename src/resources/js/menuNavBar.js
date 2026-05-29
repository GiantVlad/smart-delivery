import {
  mdiAccount,
  mdiCogOutline,
  mdiEmail,
  mdiLogout,
  mdiThemeLightDark,
  mdiReact
} from '@mdi/js'

export default [
  {
    isCurrentUser: true,
    menu: [
      {
        icon: mdiAccount,
        label: 'My Profile',
        isProfile: true,
      },
      {
        icon: mdiCogOutline,
        label: 'Settings'
      },
      {
        icon: mdiEmail,
        label: 'Messages'
      },
      {
        isDivider: true
      },
      {
        icon: mdiLogout,
        label: 'Log Out',
        isCurrentUser: true,
        isLogout: true
      }
    ]
  },
  {
    icon: mdiThemeLightDark,
    label: 'Light/Dark',
    isDesktopNoLabel: true,
    isToggleLightDark: true
  }
]

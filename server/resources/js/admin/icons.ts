export const iconPaths = {
  home: 'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z',
  template: 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
  image:
    'M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z M3 17l5-5 4 4 4-6 5 7 M8 7h.01',
  tasks:
    'M8 4H5a1 1 0 0 0-1 1v15h16V5a1 1 0 0 0-1-1h-3 M8 3h8v4H8z M8 12h8 M8 16h5',
  users:
    'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
  wallet:
    'M20 8V5a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h16v12H5a2 2 0 0 1-2-2V6 M21 12h-5v4h5 M17 14h.01',
  support:
    'M3 14v-3a9 9 0 0 1 18 0v6a4 4 0 0 1-4 4h-5 M3 11h4v7H3z M17 11h4v7h-4z',
  shield: 'M12 3 3 7v5c0 5 9 9 9 9s9-4 9-9V7Z M8 12l3 3 5-6',
  content: 'M14 2H5v20h14V7Z M14 2v6h5 M8 12h8 M8 16h8',
  chart: 'M3 3v18h18 M7 16v-4 M12 16V7 M17 16v-7',
  audit:
    'M10 3H4v18h16v-6 M8 8h3 M8 12h3 M8 16h6 M18 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M21 10l2 2',
  menu: 'M4 6h16 M4 12h16 M4 18h16',
  fold: 'M4 5h16 M4 19h16 M10 9l-3 3 3 3 M15 9h5 M15 15h5',
  refresh:
    'M20 7v5h-5 M4 17v-5h5 M6 6a8 8 0 0 1 13 2l1 4 M4 12l1 4a8 8 0 0 0 13 2',
  expand: 'M8 3H3v5 M16 3h5v5 M3 16v5h5 M21 16v5h-5',
  logout: 'M9 3H3v18h6 M14 7l5 5-5 5 M8 12h13',
  arrow: 'M5 12h14 M14 7l5 5-5 5',
  down: 'm6 9 6 6 6-6',
  close: 'm6 6 12 12 M6 18 18 6',
  clock: 'M21 12a9 9 0 1 0-18 0 9 9 0 0 0 18 0 M12 7v5l3 2',
} as const;

export type AdminIconName = keyof typeof iconPaths;

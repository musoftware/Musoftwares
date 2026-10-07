import '@testing-library/jest-dom'
import { loadTranslations } from '@/lib/i18n'

// The app loads the active locale before rendering; tests use English.
await loadTranslations('en')

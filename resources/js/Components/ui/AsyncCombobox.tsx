import { __ } from '@/lib/i18n';
import * as React from "react"
import { Check, ChevronsUpDown, Loader2 } from "lucide-react"
import axios from "axios"

import { cn } from "@/lib/utils"
import { Button, buttonVariants } from "@/Components/ui/button"
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/Components/ui/command"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/Components/ui/popover"

interface Option {
  id: string | number
  name: string
  [key: string]: any
}

interface AsyncComboboxProps {
  endpoint: string
  value: string | number | null
  onChange: (value: string | number | null, option: Option | null) => void
  placeholder?: string
  emptyText?: string
  className?: string
  debounceMs?: number
  defaultOptions?: Option[]
  initialLabel?: string
  disabled?: boolean
  searchParam?: string
  prependOptions?: Option[]
}

export function AsyncCombobox({
  endpoint,
  value,
  onChange,
  placeholder = "Select an option...",
  emptyText = "No results found.",
  className,
  debounceMs = 300,
  defaultOptions = [],
  initialLabel = "",
  disabled = false,
  searchParam = "q",
  prependOptions = []
}: AsyncComboboxProps) {
  const [open, setOpen] = React.useState(false)
  const [searchQuery, setSearchQuery] = React.useState("")
  const [options, setOptions] = React.useState<Option[]>(defaultOptions)
  const [loading, setLoading] = React.useState(false)
  const [selectedLabel, setSelectedLabel] = React.useState(initialLabel)

  // Use a ref to store the latest debounce timeout
  const timeoutRef = React.useRef<NodeJS.Timeout | null>(null)

  const fetchOptions = async (query: string) => {
    try {
      setLoading(true)
      const response = await axios.get(endpoint, {
        params: { [searchParam]: query }
      })
      // Handle standard Laravel pagination or direct arrays
      const data = response.data?.data || response.data
      setOptions(Array.isArray(data) ? data : [])
    } catch (error: any) {
      console.error("AsyncCombobox fetch error:", error)
      setOptions([])
    } finally {
      setLoading(false)
    }
  }

  React.useEffect(() => {
    // Only search if popover is open
    if (!open) return

    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current)
    }

    // Debounce the fetch request
    timeoutRef.current = setTimeout(() => {
      fetchOptions(searchQuery)
    }, debounceMs)

    return () => {
      if (timeoutRef.current) clearTimeout(timeoutRef.current)
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchQuery, open, endpoint])

  // Reset options when opened without a search query to show default state
  React.useEffect(() => {
    if (open && !searchQuery && defaultOptions.length > 0) {
      setOptions(defaultOptions)
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open])

  // Support updating selectedLabel when initialLabel prop changes
  React.useEffect(() => {
    if (initialLabel) {
      setSelectedLabel(initialLabel)
    }
  }, [initialLabel])

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger
          role="combobox"
          aria-expanded={open}
          className={cn(buttonVariants({ variant: "outline" }), "w-full justify-between shadow-none font-normal", className)}
          disabled={disabled}
        >
          <span className="truncate">
            {value ? selectedLabel || "Selected" : placeholder}
          </span>
          <ChevronsUpDown className="ms-2 h-4 w-4 shrink-0 opacity-50" />
      </PopoverTrigger>
      <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput 
            placeholder={__('general.search')} 
            value={searchQuery}
            onValueChange={setSearchQuery}
          />
          <CommandList>
            <CommandEmpty>
              {loading ? (
                <div className="flex items-center justify-center py-6 text-sm text-slate-500">
                  <Loader2 className="me-2 h-4 w-4 animate-spin" />{__('general.searching')}</div>
              ) : (
                emptyText
              )}
            </CommandEmpty>
            <CommandGroup>
              {/* Render prepended options first (e.g., 'All Clients') */}
              {!loading && prependOptions.map((option) => (
                <CommandItem
                  key={option.id}
                  value={option.id.toString()}
                  onSelect={() => {
                    const newValue = option.id.toString() === value?.toString() ? null : option.id
                    onChange(newValue, newValue ? option : null)
                    setSelectedLabel(newValue ? option.name : "")
                    setOpen(false)
                  }}
                >
                  <Check
                    className={cn(
                      "me-2 h-4 w-4",
                      value?.toString() === option.id.toString() ? "opacity-100" : "opacity-0"
                    )}
                  />
                  {option.name}
                </CommandItem>
              ))}

              {/* Render fetched options */}
              {!loading && options.filter(opt => !prependOptions.find(p => p.id === opt.id)).map((option) => (
                <CommandItem
                  key={option.id}
                  value={option.id.toString()}
                  onSelect={() => {
                    const newValue = option.id.toString() === value?.toString() ? null : option.id
                    onChange(newValue, newValue ? option : null)
                    setSelectedLabel(newValue ? option.name : "")
                    setOpen(false)
                  }}
                >
                  <Check
                    className={cn(
                      "me-2 h-4 w-4",
                      value?.toString() === option.id.toString() ? "opacity-100" : "opacity-0"
                    )}
                  />
                  {option.name}
                </CommandItem>
              ))}
              {loading && options.length > 0 && (
                <div className="flex items-center justify-center py-2 text-xs text-slate-500">
                  <Loader2 className="me-2 h-3 w-3 animate-spin" />{__('general.loading_more')}</div>
              )}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  )
}

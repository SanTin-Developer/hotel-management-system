import { Tabs as TabsPrimitive } from "@base-ui/react/tabs"

import { cn } from "@/lib/utils"

function Tabs({ value, onValueChange, ...props }) {
  return (
    <TabsPrimitive.Root
      data-slot="tabs"
      value={value}
      onValueChange={onValueChange}
      {...props} />
  );
}

function TabsList({ className, ...props }) {
  return (
    <TabsPrimitive.List
      data-slot="tabs-list"
      className={cn(
        "relative inline-flex h-10 w-fit items-center justify-center rounded-full border border-border bg-white p-1 text-muted-foreground",
        className
      )}
      {...props} />
  );
}

function TabsTrigger({ className, ...props }) {
  return (
    <TabsPrimitive.Tab
      data-slot="tabs-trigger"
      className={cn(
        "inline-flex h-8 shrink-0 items-center justify-center rounded-full px-4 text-sm font-medium whitespace-nowrap transition-colors outline-none select-none focus-visible:ring-3 focus-visible:ring-ring/50 data-[selected]:bg-brand-900 data-[selected]:text-white data-[hovered]:text-brand-900 data-[hovered]:data-[selected]:text-white",
        className
      )}
      {...props} />
  );
}

function TabsContent({ className, ...props }) {
  return (
    <TabsPrimitive.Panel
      data-slot="tabs-content"
      className={cn("flex-1 outline-none", className)}
      {...props} />
  );
}

export { Tabs, TabsList, TabsTrigger, TabsContent }
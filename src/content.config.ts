import { defineCollection, z } from "astro:content";
import { file } from "astro/loaders";

function verseParser(content: string) {
  return content 
    .split('\n')
    .slice(0, 6236)
    .map(row => {
      const [chapter, verse, text] = row.split('|')

      return { id: `${chapter}-${verse}`, chapter: +chapter, verse: +verse, text }
    })
}

const verses = defineCollection({
  loader: file('src/data/verses.txt', { parser: verseParser }),
  schema: z.object({
    chapter: z.number(),
    verse: z.number(),
    text: z.string()
  })
})

export const collections = { verses }

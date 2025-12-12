import { defineCollection, z } from "astro:content";
import { file } from "astro/loaders";
import parser from 'xml-parser'

function verseParser(content: string) {
  return content 
    .split('\n')
    .slice(0, 6236)
    .map(row => {
      const [chapter, verse, text] = row.split('|')

      return { id: `${chapter}-${verse}`, chapter: +chapter, verse: +verse, text }
    })
}

function chapterParser(content: string) {
  const data = parser(content)
  const chapters = data.root.children.find(child => child.name === 'suras')

  if (!chapters) {
    return []
  }

  return chapters.children.map(child => ({
    id: child.attributes.index,
    verses: +child.attributes.ayas,
    start: +child.attributes.start,
    name: child.attributes.name,
    latin: child.attributes.tname,
    translate: child.attributes.ename,
    type: child.attributes.type
  }))
}

const verses = defineCollection({
  loader: file('src/data/verses.txt', { parser: verseParser }),
  schema: z.object({
    chapter: z.number(),
    verse: z.number(),
    text: z.string()
  })
})

const chapters = defineCollection({
  loader: file('src/data/quran-data.xml', { parser: chapterParser }),
  schema: z.object({
    verses: z.number(),
    start: z.number(),
    name: z.string(),
    latin: z.string(),
    translate: z.string(),
    type: z.enum(['Meccan', 'Medinan'])
  })
})

export const collections = { verses, chapters }

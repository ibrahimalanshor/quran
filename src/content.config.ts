import { defineCollection, z } from "astro:content";
import { file } from "astro/loaders";
import { Parser } from 'xml2js'

const xmlParser = new Parser({ explicitArray: false })

function verseParser(content: string) {
  return content 
    .split('\n')
    .slice(0, 6236)
    .map(row => {
      const [chapter, verse, text] = row.split('|')

      return { id: `${chapter}-${verse}`, chapter: +chapter, verse: +verse, text }
    })
}

async function chapterParser(content: string) {
  const res = await xmlParser.parseStringPromise(content)

  return res.quran.suras.sura.map(sura => ({
    id: sura.$.index,
    verses: sura.$.ayas,
    start: sura.$.start,
    name: sura.$.name,
    latin: sura.$.tname,
    translate: sura.$.ename,
    type: sura.$.type
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

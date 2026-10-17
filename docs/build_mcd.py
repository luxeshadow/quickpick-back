"""Generate editable diagrams.net XML and previews from schema metadata (no records)."""
import json
import math
import html
from pathlib import Path
import xml.etree.ElementTree as ET
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
OUT = ROOT / 'mcd'
OUT.mkdir(exist_ok=True)
schema = json.loads((ROOT / 'quickpick-schema-metadata.json').read_text(encoding='utf-8'))
FONT = 'C:/Windows/Fonts/segoeui.ttf'
BOLD = 'C:/Windows/Fonts/segoeuib.ttf'
navy, teal, ink, muted = '#142D4E', '#087E8B', '#18344B', '#53677D'
mx = ET.Element('mxfile', host='app.diagrams.net', agent='QuickPick schema documentation', version='24.7.17')
previews = []
NAMES = {'users':'UTILISATEUR','subscription_plans':'PLAN ABONNEMENT','subscriptions':'ABONNEMENT',
 'payments':'PAIEMENT','invoices':'FACTURE','events':'EVENEMENT','media':'MEDIA',
 'access_links':'LIEN ACCES','guest_scans':'SCAN INVITE','media_deliveries':'LIVRAISON',
 'upload_batches':'LOT UPLOAD','upload_batch_items':'ELEMENT UPLOAD',
 'roles':'ROLE','permissions':'PERMISSION','photo_faces':'VISAGE INDEXE (Supabase)'}
FIELDS = {
 'users':['id','full_name','email','phone','status','email_verified_at'],
 'subscription_plans':['id','name','description','price','duration_days','max_events','max_photos','accept_video','max_videos','is_active','deleted_at'],
 'subscriptions':['id','status','starts_at','ends_at','cancelled_at'],
 'payments':['id','provider','payment_method','amount','currency','status','external_reference','paid_at'],
 'invoices':['id','invoice_number','subtotal','tax','total','currency','status','issued_at','due_at','paid_at'],
 'events':['id','name','description','location','event_date','status','media_status','media_count','deleted_at'],
 'media':['id','media_type','storage_path','original_filename','mime_type','file_size','width','height','processing_status','faces_count','model_version','uploaded_at'],
 'access_links':['id','token','source','expires_at','is_active','max_scans','scans_count','last_used_at'],
 'guest_scans':['id','status','matches_count','best_similarity','error_message'],
 'media_deliveries':['id','channel','recipient','status','error_message','sent_at'],
 'upload_batches':['id','status','expected_count','uploaded_count','failed_count'],
 'upload_batch_items':['id','filename','status','error'],
 'roles':['id','name','guard_name'], 'permissions':['id','name','guard_name'],
 'photo_faces':['id','face_index','confidence','x','y','width','height','embedding : vector(512)','model_version','created_at'],
}

def font(size=20, bold=False): return ImageFont.truetype(BOLD if bold else FONT, size)

class Page:
    def __init__(self, title, subtitle, w=1800, h=1450):
        self.w,self.h=w,h
        self.image=Image.new('RGB',(w,h),'white');self.draw=ImageDraw.Draw(self.image)
        diagram=ET.SubElement(mx,'diagram',id=f'page{len(mx)}',name=title)
        self.model=ET.SubElement(diagram,'mxGraphModel',dx='1800',dy='1450',grid='1',gridSize='10',page='1',pageScale='1',pageWidth=str(w),pageHeight=str(h))
        self.root=ET.SubElement(self.model,'root');ET.SubElement(self.root,'mxCell',id='0');ET.SubElement(self.root,'mxCell',id='1',parent='0')
        self.i=1;self.nodes={}
        self.text(title,60,32,w-120,55,34,navy,True)
        self.text(subtitle,60,100,w-120,65,19,muted)
    def cell(self,value,style,x,y,w,h):
        self.i+=1;cid=str(self.i)
        cell=ET.SubElement(self.root,'mxCell',id=cid,value=value,style=style,vertex='1',parent='1')
        ET.SubElement(cell,'mxGeometry',x=str(x),y=str(y),width=str(w),height=str(h),attrib={'as':'geometry'})
        return cid
    def text(self,text,x,y,w,h,size=20,color=ink,bold=False):
        self.cell(html.escape(text).replace('\n','<br>'),f'text;html=1;align=left;verticalAlign=top;whiteSpace=wrap;fontFamily=Segoe UI;fontSize={size};fontColor={color};fontStyle={1 if bold else 0};',x,y,w,h)
        self.draw.multiline_text((x,y),text,font=font(size,bold),fill=color,spacing=5)
    def entity(self,key,x,y,alias=None,fields=None,physical=False):
        cols=fields or FIELDS[key]
        nullable={c['name']:c['nullable'] for c in schema.get(key,{}).get('columns',[])}
        if key == 'photo_faces':
            nullable = {c: True for c in ['confidence','x','y','width','height','model_version']}
        labels=[c+(' ?' if nullable.get(c,False) else '') for c in cols]
        width=420 if physical else 410; row=25 if physical else 28
        height=55+row*len(labels)+15
        title=alias or NAMES.get(key,key)
        body='<b>'+html.escape(title)+'</b><br><font color="#53677D">'+html.escape(key)+'</font><hr>'
        body+='<br>'.join(('<u>'+html.escape(l)+'</u>') if l=='id' else html.escape(l) for l in labels)
        cid=self.cell(body,'rounded=0;html=1;whiteSpace=wrap;align=left;verticalAlign=top;spacing=14;fontFamily=Segoe UI;fontSize=19;fillColor=#F0F7FA;strokeColor=#087E8B;strokeWidth=2;',x,y,width,height)
        self.nodes[key]=(cid,x,y,width,height)
        self.draw.rectangle((x,y,x+width,y+height),fill='#F0F7FA',outline=teal,width=2)
        self.draw.text((x+14,y+8),title,font=font(21,True),fill=navy)
        self.draw.text((x+14,y+34),key,font=font(15),fill=muted)
        for j,label in enumerate(labels):
            yy=y+59+j*row
            self.draw.text((x+14,yy),label,font=font(18),fill=ink)
            if label=='id':self.draw.line((x+14,yy+22,x+32,yy+22),fill=ink)
        return key
    def association(self,a,b,label,x,y,ca='0,N',cb='1,1',attributes=None,dashed=False):
        lines=[label]+(attributes or [])
        width=250;height=75 if not attributes else 130
        val='<b>'+html.escape(label)+'</b>'+(''.join('<br>'+html.escape(t) for t in (attributes or [])))
        cid=self.cell(val,'ellipse;html=1;whiteSpace=wrap;fontFamily=Segoe UI;fontSize=19;fillColor=#FFF4DD;strokeColor=#C78B24;strokeWidth=2;',x,y,width,height)
        # Edges are inserted before vertices so entities remain on top of connectors.
        for key,card in [(a,ca),(b,cb)]:
            nid,nx,ny,nw,nh=self.nodes[key]
            self.i+=1
            edge=ET.Element('mxCell',id=str(self.i),value='',style='edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;endArrow=none;startArrow=none;strokeColor=#71869B;strokeWidth=2;'+('dashed=1;' if dashed else ''),edge='1',parent='1',source=nid,target=cid)
            ET.SubElement(edge,'mxGeometry',relative='1',attrib={'as':'geometry'})
            self.root.insert(2,edge)
            # Cardinality label attached to its edge, at the entity end.
            self.i+=1
            lab=ET.SubElement(self.root,'mxCell',id=str(self.i),value=card,style='edgeLabel;html=1;align=center;verticalAlign=middle;fontSize=19;fontColor=#142D4E;labelBackgroundColor=#FFFFFF;',vertex='1',connectable='0',parent=edge.attrib['id'])
            geo=ET.SubElement(lab,'mxGeometry',x='-0.70',y='-12',relative='1',attrib={'as':'geometry'});ET.SubElement(geo,'mxPoint',x='0',y='0',attrib={'as':'offset'})
            sx,sy=nx+nw/2,ny+nh/2; tx,ty=x+width/2,y+height/2
            dx,dy=tx-sx,ty-sy
            factor=min(nw/2/max(abs(dx),1e-8),nh/2/max(abs(dy),1e-8))
            start=(sx+dx*factor,sy+dy*factor)
            ef=min(width/2/max(abs(dx),1e-8),height/2/max(abs(dy),1e-8))
            end=(tx-dx*ef,ty-dy*ef)
            self.draw.line((start,end),fill='#71869B',width=3)
            cx,cy=start[0]+(end[0]-start[0])*.15,start[1]+(end[1]-start[1])*.15
            box=self.draw.textbbox((cx,cy-26),card,font=font(19,True));self.draw.rectangle(box,fill='white')
            self.draw.text((cx,cy-26),card,font=font(19,True),fill=navy)
        self.draw.ellipse((x,y,x+width,y+height),fill='#FFF4DD',outline='#C78B24',width=2)
        for j,line in enumerate(lines):
            bbox=self.draw.textbbox((0,0),line,font=font(19,j==0))
            self.draw.text((x+(width-(bbox[2]-bbox[0]))/2,y+19+j*27),line,font=font(19,j==0),fill=ink)
    def finish(self,notes):
        self.text(notes,60,self.h-140,self.w-120,110,18,muted)
        self.text('QuickPick · MCD / état inspecté le 17 octobre 2026 · ? = attribut facultatif',60,self.h-36,self.w-120,28,15,muted)
        path=OUT/f'{len(previews)+1:02d}-apercu.png';self.image.save(path);previews.append(path)

def square(title,subtitle,keys,relations,notes):
    page=Page(title,subtitle)
    for key,(x,y) in zip(keys,[(90,210),(1270,210),(90,860),(1270,860)]):page.entity(key,x,y)
    places={'top':(770,300),'left':(170,690),'right':(1350,690),'bottom':(770,970),'center':(770,680)}
    for a,b,label,place,ca,cb,*rest in relations:page.association(a,b,label,*places[place],ca,cb,*(rest or []))
    page.finish(notes)

square('01 · Comptes et abonnements','MCD Merise : entités, associations et cardinalités lues du côté de chaque entité.',
 ['users','subscriptions','payments','subscription_plans'],[
 ('users','subscriptions','SOUSCRIRE','top','0,N','1,1'),
 ('users','payments','EFFECTUER','left','0,N','1,1'),
 ('subscriptions','subscription_plans','RELEVER DE','right','1,1','0,N'),
 ('payments','subscriptions','REGLER','center','1,1','0,N'),
 ],'Un abonnement appartient à un seul utilisateur et à un seul plan. Un plan peut être souscrit plusieurs fois.\nends_at et duration_days peuvent être NULL : offre permanente. Les plans supprimés restent référencés par les abonnements.')

square('02 · Facturation','Les liens utilisateur, abonnement et paiement sont tous présents dans la table invoices.',
 ['users','subscriptions','payments','invoices'],[
 ('users','invoices','RECEVOIR','center','0,N','1,1'),
 ('subscriptions','invoices','FACTURER','right','0,N','1,1'),
 ('payments','invoices','JUSTIFIER','bottom','0,1','1,1'),
 ],'payment_id est unique dans invoices : un paiement peut justifier zéro ou une facture.\nLa cohérence utilisateur / abonnement / paiement doit aussi être assurée par le code métier ; les FK ne la garantissent pas seules.')

square('03 · Evénements et médias','Le nom courant est media : une même entité représente les images et les vidéos.',
 ['users','events','access_links','media'],[
 ('users','events','ORGANISER','top','0,N','1,1'),
 ('events','media','CONTENIR','right','0,N','1,1'),
 ('events','access_links','OUVRIR PAR','center','0,N','1,1'),
 ('users','access_links','CREER','left','0,N','0,1'),
 ],'created_by est facultatif dans les liens. Un lien peut être créé automatiquement.\nLes événements sont archivables via deleted_at. Les compteurs de médias sont des agrégats stockés, pas des entités.')

square('04 · Reconnaissance faciale','Le selfie est transitoire : aucune entité PHOTO SELFIE persistée dans le schéma actuel.',
 ['events','media','guest_scans','photo_faces'],[
 ('events','media','CONTENIR','top','0,N','1,1'),
 ('events','guest_scans','RECEVOIR SCAN','left','0,N','1,1'),
 ('media','photo_faces','DETECTER','right','0,N','1,1',None,True),
 ('guest_scans','media','CORRESPONDRE','center','0,N','0,N',['similarity · rank','face_index ?']),
 ],'CORRESPONDRE devient guest_scan_matches (paire scan / média unique). Un scan peut ne trouver aucune image.\nVISAGE INDEXE est dans Supabase : photo_id désigne media.id ; event_id est aussi stocké. Aucune FK inter-base.\nSFace : 128 valeurs normalisées + 384 zéros ; stockage vector(512). Les vidéos ne sont jamais vectorisées.')

square('05 · Scans et livraisons','Deux parcours existent : sélection manuelle via un lien, ou sélection issue d’un scan.',
 ['access_links','guest_scans','media_deliveries','media'],[
 ('access_links','guest_scans','AUTORISER','top','0,N','0,1'),
 ('guest_scans','media_deliveries','PROVOQUER','center','0,N','0,1'),
 ('access_links','media_deliveries','DEMANDER','left','0,N','0,1'),
 ('media_deliveries','media','INCLURE','bottom','0,N','0,N'),
 ],'INCLURE devient media_delivery_items (paire livraison / média unique). Le code attend une sélection non vide.\nLa base autorise zéro élément ainsi que guest_scan_id et access_link_id tous deux NULL : pas de contrainte XOR.\nLe lien du scan peut devenir NULL après suppression du lien ; les cardinalités reflètent cette possibilité.')

p=Page('06 · Suivi des uploads','Le lot décrit une tentative d’upload. Le rattachement direct media.upload_batch_id a été supprimé.',h=2100)
for k,x,y in [('users',90,210),('events',1270,210),('upload_batches',90,860),('upload_batch_items',1270,860),('media',1270,1430)]: p.entity(k,x,y)
p.association('users','upload_batches','INITIER',170,690,'0,N','1,1')
p.association('events','upload_batches','REGROUPER',770,680,'0,N','1,1')
p.association('upload_batches','upload_batch_items','DETAILLER',770,970,'0,N','1,1')
p.association('upload_batch_items','media','ABOUTIR A',1350,1280,'0,1','0,N')
p.finish('upload_batch_items.media_id est facultatif : un upload peut échouer avant création du média.\nUn média peut être référencé par plusieurs éléments ; aucune contrainte unique sur media_id.\ncreated_by et event_id sont obligatoires dans un lot. Le média appartient toujours à un événement (onglet 03).')

business=['users','subscription_plans','subscriptions','payments','invoices','events','media','access_links','guest_scans','guest_scan_matches','media_deliveries','media_delivery_items','upload_batches','upload_batch_items']
for title,tables in [('07 · Schéma physique métier',business)]:
    p=Page(title,'Annexe MLD / MPD : colonnes réellement présentes dans PostgreSQL local ; PK, FK et NULL sont indiqués.',w=2400,h=2700)
    ypos=[220]*4
    for t in tables:
        col=min(range(4),key=lambda k:ypos[k]);x=60+col*590;y=ypos[col]
        data=schema[t];pks=set(next((idx['columns'] for idx in data['indexes'] if idx['primary']),[]))
        fks={c for fk in data['foreign_keys'] for c in fk['columns']}
        fields=[('PK ' if c['name'] in pks else '')+('FK ' if c['name'] in fks else '')+c['name']+(' ?' if c['nullable'] else '')+' : '+c['type_name'] for c in data['columns']]
        p.entity(t,x,y,alias=t,fields=fields,physical=True)
        ypos[col]=y+55+25*len(fields)+15+60
    p.finish('Références et règles de suppression : voir le dictionnaire joint. Les références polymorphes ne sont pas des FK SQL.\nLes données Supabase ne figurent pas dans cette inspection PostgreSQL locale ; photo_faces est documentée séparément.')

ET.indent(mx)
target=OUT/'QuickPick-MCD.drawio';ET.ElementTree(mx).write(target,encoding='utf-8',xml_declaration=True)
# Structural validation: unique IDs and every connector / label targets an existing cell.
check=ET.parse(target)
for diagram in check.getroot().findall('diagram'):
    cells=diagram.findall('.//mxCell');ids=[c.get('id') for c in cells]
    assert len(ids)==len(set(ids))
    for c in cells:
        for name in ['source','target','parent']:
            assert c.get(name) is None or c.get(name) in ids, (diagram.get('name'),c.attrib)

parts=['# QuickPick — MCD des tables métier uniquement\n',
 'État : 17 octobre 2026. Source principale : métadonnées en lecture seule de PostgreSQL local ; migrations et modèles Laravel. Périmètre : 14 tables métier locales et photo_faces dans Supabase. Aucun enregistrement utilisateur exporté.\n',
 'Ouvrir QuickPick-MCD.drawio dans diagrams.net via Fichier > Ouvrir depuis > Appareil. Les objets et associations sont modifiables.\n',
 '## Convention\nEntités rectangulaires, identifiant souligné, associations ovales ; cardinalités placées près de chaque entité. 0,N = zéro à plusieurs ; 1,1 = exactement un ; 0,1 = facultatif. ? = nullable en base.\n',
 'Les entités répétées dans différents onglets désignent la même entité. Les 6 premiers onglets sont conceptuels ; le dernier est physique et conserve les FK. Les tables techniques Laravel, Sanctum et Spatie sont exclues. users reste présente car elle porte les comptes QuickPick et les relations métier.\n',
 '## Limites et règles réelles\n- La table Supabase photo_faces est distincte de PostgreSQL local. Sa structure provient du schéma fourni et du script docs/supabase/002_sface_search.sql ; elle n’a pas été inspectée directement pour ce document.\n- Les liens Supabase photo_id → media.id et event_id → events.id sont logiques, sans FK inter-base.\n- guest_scan_matches stocke la meilleure face par média et par scan (paire unique), pas toutes les faces détectées.\n- Les livraisons peuvent référencer un lien, un scan, les deux ou aucun selon les contraintes SQL ; la logique métier doit limiter ces cas.\n- Un seul abonnement courant n’est pas garanti par une contrainte unique SQL. La sélection dépend du statut et des dates.\n- Seuls events et subscription_plans possèdent deleted_at dans le schéma inspecté.\n- Supabase Storage contient les fichiers ; PostgreSQL conserve leurs chemins, pas leurs octets.\n- Les attributs conceptuels sont sélectionnés pour la lisibilité ; le dictionnaire et les onglets physiques listent toutes les colonnes.\n',
 '## Dictionnaire physique exhaustif\n']
for t in business:
    data = schema[t]
    parts += [f'### {t}\n','| Colonne | Type | Nullable |\n|---|---|---|\n']
    parts += [f"| {c['name']} | {c['type_name']} | {'oui' if c['nullable'] else 'non'} |\n" for c in data['columns']]
    for fk in data['foreign_keys']:
        parts.append(f"\n- FK {', '.join(fk['columns'])} → {fk['foreign_table']}({', '.join(fk['foreign_columns'])}), suppression : {fk.get('on_delete','non renseigné')}.\n")
    for idx in data['indexes']:
        if idx['unique']:parts.append(f"- {'PK' if idx['primary'] else 'UNIQUE'} : {', '.join(idx['columns'])}.\n")
    parts.append('\n')
(OUT/'LISEZ-MOI.md').write_text(''.join(parts),encoding='utf-8')
thumbs=Image.new('RGB',(1500,math.ceil(len(previews)/3)*470),'#E6EDF2');d=ImageDraw.Draw(thumbs)
for i,path in enumerate(previews):
    im=Image.open(path);im.thumbnail((480,420));x=10+(i%3)*500;y=10+(i//3)*470
    thumbs.paste(im,(x,y));d.text((x,y+425),f'Onglet {i+1:02d}',font=font(20,True),fill=navy)
thumbs.save(OUT/'apercu-ensemble.png')
print(f'{target}: {len(previews)} onglets ; XML validé ; 14 tables métier locales + photo_faces Supabase.')

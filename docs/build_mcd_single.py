"""Single-page editable block diagram of QuickPick business tables."""
import heapq
import html
import json
import xml.etree.ElementTree as ET
from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
OUT = ROOT / 'mcd'
schema = json.loads((ROOT / 'quickpick-schema-metadata.json').read_text(encoding='utf-8'))
schema['photo_faces'] = {'columns': [dict(name=n,type_name=t,nullable=z) for n,t,z in [
    ('id','bigint',False),('photo_id','bigint',False),('event_id','bigint',False),
    ('face_index','integer',False),('confidence','numeric',True),('x','integer',True),
    ('y','integer',True),('width','integer',True),('height','integer',True),
    ('embedding','vector(512)',False),('model_version','varchar',True),('created_at','timestamptz',False)]],
    'indexes': [], 'foreign_keys': []}
layout = [
 ['subscription_plans','subscriptions','payments','invoices','photo_faces'],
 ['users','events','access_links','guest_scans','media'],
 ['upload_batches','upload_batch_items','media_deliveries','media_delivery_items','guest_scan_matches'],
]
W,H=3300,2770
image=Image.new('RGB',(W,H),'#F5F8FC');draw=ImageDraw.Draw(image)
font=lambda s=20,b=False:ImageFont.truetype('C:/Windows/Fonts/segoeuib.ttf' if b else 'C:/Windows/Fonts/segoeui.ttf',s)
mx=ET.Element('mxfile',host='app.diagrams.net',version='24.7.17')
diagram=ET.SubElement(mx,'diagram',id='quickpick-single',name='QuickPick — toutes les tables métier')
model=ET.SubElement(diagram,'mxGraphModel',grid='1',gridSize='10',page='1',pageWidth=str(W),pageHeight=str(H),pageScale='1')
root=ET.SubElement(model,'root');ET.SubElement(root,'mxCell',id='0');ET.SubElement(root,'mxCell',id='1',parent='0')

def text(cid,value,x,y,w,h,size=22,color='#18344B'):
    c=ET.SubElement(root,'mxCell',id=cid,value=html.escape(value).replace('\n','<br>'),parent='1',vertex='1',style=f'text;html=1;align=left;verticalAlign=top;fontSize={size};fontColor={color};fontFamily=Segoe UI;')
    ET.SubElement(c,'mxGeometry',x=str(x),y=str(y),width=str(w),height=str(h),attrib={'as':'geometry'})
    draw.multiline_text((x,y),value,font=font(size),fill=color,spacing=9)

text('title','QUICKPICK · MODÈLE DE DONNÉES MÉTIER',70,40,3100,65,42,'#142D4E')
text('subtitle','Une seule page · 14 tables métier PostgreSQL + photo_faces Supabase · aucune table technique Laravel / Spatie',70,110,3100,50,24)
nodes={}
for row,tables in enumerate(layout):
    for col,name in enumerate(tables):
        x,y=80+col*640,240+row*800
        data=schema[name]
        fields=[c for c in data['columns'] if c['name'] not in ['created_at','updated_at']]
        h=100+len(fields)*25;w=450
        pk=set(next((i['columns'] for i in data['indexes'] if i.get('primary')),['id']))
        fk={c for f in data['foreign_keys'] for c in f['columns']}
        if name=='photo_faces':fk={'photo_id','event_id'}
        rows=[]
        for c in fields:
            prefix='PK ' if c['name'] in pk else ('FK ' if c['name'] in fk else '')
            rows.append(prefix+c['name']+(' ?' if c['nullable'] else '')+' : '+c['type_name'])
        rows.append('created_at / updated_at' if name!='photo_faces' else 'created_at')
        fill='#FFF7E9' if name=='photo_faces' else '#FFFFFF'
        color='#B17A21' if name=='photo_faces' else '#087E8B'
        val='<b>'+name+'</b><hr>'+ '<br>'.join(html.escape(r) for r in rows)
        c=ET.SubElement(root,'mxCell',id=name,value=val,parent='1',vertex='1',style=f'rounded=1;arcSize=4;html=1;whiteSpace=wrap;align=left;verticalAlign=top;spacing=16;fontFamily=Segoe UI;fontSize=20;fillColor={fill};strokeColor={color};strokeWidth=2;')
        ET.SubElement(c,'mxGeometry',x=str(x),y=str(y),width=str(w),height=str(h),attrib={'as':'geometry'})
        nodes[name]=(x,y,w,h)
        draw.rounded_rectangle((x,y,x+w,y+h),radius=9,fill=fill,outline=color,width=3)
        draw.text((x+18,y+13),name,font=font(25,True),fill='#142D4E')
        draw.line((x+16,y+53,x+w-16,y+53),fill=color,width=2)
        for i,line in enumerate(rows):draw.text((x+18,y+63+i*25),line,font=font(18),fill='#18344B')

edges=[]
for child in nodes:
    for fk in schema[child]['foreign_keys']:
        parent=fk['foreign_table']
        if parent not in nodes:continue
        optional=any(next(c for c in schema[child]['columns'] if c['name']==n)['nullable'] for n in fk['columns'])
        unique=any(i['unique'] and set(i['columns'])==set(fk['columns']) for i in schema[child]['indexes'])
        edges.append((parent,child,','.join(fk['columns']),'0,1' if unique else '0,N','0,1' if optional else '1,1',False))
edges += [('media','photo_faces','photo_id','0,N','1,1',True),('events','photo_faces','event_id','0,N','1,1',True)]
step=20
occupied=set()
for x,y,w,h in nodes.values():
    for gx in range((x-10)//step,(x+w+10)//step+1):
        for gy in range((y-10)//step,(y+h+10)//step+1):occupied.add((gx,gy))
usage={};ports={}
def anchor(name,other):
    x,y,w,h=nodes[name];a,b,c,d=nodes[other]
    dx=a+c/2-x-w/2;dy=b+d/2-y-h/2
    if abs(dx)>abs(dy)*.65: side='right' if dx>0 else 'left'
    else:side='bottom' if dy>0 else 'top'
    count=ports.get((name,side),0);ports[name,side]=count+1
    delta=(count//2+1)*40*(1 if count%2 else -1) if count else 0
    if side in ['left','right']:
        yy=round((y+h/2+delta)/step)*step;yy=max(y+60,min(y+h-30,yy))
        xx=x if side=='left' else x+w
        end=(round((xx+(-40 if side=='left' else 40))/step),round(yy/step))
        pos=(xx,yy);relative=(0 if side=='left' else 1,(yy-y)/h)
    else:
        xx=round((x+w/2+delta)/step)*step;xx=max(x+30,min(x+w-30,xx))
        yy=y if side=='top' else y+h
        end=(round(xx/step),round((yy+(-40 if side=='top' else 40))/step))
        pos=(xx,yy);relative=((xx-x)/w,0 if side=='top' else 1)
    return pos,end,relative

def route(start,goal):
    queue=[(0,0,start)];cost={start:0};prev={}
    while queue:
        _,g,current=heapq.heappop(queue)
        if g!=cost[current]:continue
        if current==goal:break
        for dx,dy in [(1,0),(-1,0),(0,1),(0,-1)]:
            nxt=(current[0]+dx,current[1]+dy)
            if nxt in occupied or not (1<=nxt[0]<W//step and 9<=nxt[1]<(H-190)//step):continue
            ng=g+1+usage.get(nxt,0)*.18
            if ng<cost.get(nxt,1e12):
                cost[nxt]=ng;prev[nxt]=current
                heuristic=abs(nxt[0]-goal[0])+abs(nxt[1]-goal[1])
                heapq.heappush(queue,(ng+heuristic,ng,nxt))
    if goal not in cost:raise RuntimeError(f'No connector route: {start} -> {goal}')
    path=[goal]
    while path[-1]!=start:path.append(prev[path[-1]])
    path.reverse()
    for point in path:usage[point]=usage.get(point,0)+1
    simplified=[path[0]]
    for i in range(1,len(path)-1):
        before,now,after=path[i-1:i+2]
        if (now[0]-before[0],now[1]-before[1])!=(after[0]-now[0],after[1]-now[1]):simplified.append(now)
    simplified.append(path[-1])
    return [(x*step,y*step) for x,y in simplified]

for i,(parent,child,label,pc,cc,external) in enumerate(edges):
    sp,sg,sr=anchor(parent,child);tp,tg,tr=anchor(child,parent)
    points=[sp]+route(sg,tg)+[tp]
    color='#B17A21' if external else '#5A7089'
    style=f'edgeStyle=none;rounded=0;html=1;endArrow=none;startArrow=none;strokeColor={color};strokeWidth=2;exitX={sr[0]};exitY={sr[1]};entryX={tr[0]};entryY={tr[1]};'
    if external:style+='dashed=1;'
    e=ET.Element('mxCell',id=f'edge{i}',parent='1',edge='1',source=parent,target=child,style=style)
    geometry=ET.SubElement(e,'mxGeometry',relative='1',attrib={'as':'geometry'})
    array=ET.SubElement(geometry,'Array',attrib={'as':'points'})
    for x,y in points[1:-1]:ET.SubElement(array,'mxPoint',x=str(x),y=str(y))
    root.insert(2,e)
    draw.line(points,fill=color,width=2)
    for suffix,card,rel,pos,nxt in [('parent',pc,-.8,points[0],points[1]),('child',cc,.8,points[-1],points[-2])]:
        c=ET.SubElement(root,'mxCell',id=f'edge{i}-{suffix}',parent=f'edge{i}',vertex='1',connectable='0',value=card,
            style='edgeLabel;html=1;fontSize=18;fontColor=#142D4E;labelBackgroundColor=#FFFFFF;')
        geo=ET.SubElement(c,'mxGeometry',x=str(rel),y='-12',relative='1',attrib={'as':'geometry'})
        ET.SubElement(geo,'mxPoint',x='0',y='0',attrib={'as':'offset'})
        dx,dy=nxt[0]-pos[0],nxt[1]-pos[1];length=max(abs(dx)+abs(dy),1)
        tx,ty=pos[0]+dx/length*22,pos[1]+dy/length*22-22
        box=draw.textbbox((tx,ty),card,font=font(17,True));draw.rectangle(box,fill='white');draw.text((tx,ty),card,font=font(17,True),fill='#142D4E')

text('legend','PK : identifiant     FK : référence     ? : facultatif     1,1 : exactement un     0,1 : zéro ou un     0,N : zéro à plusieurs',70,2540,3100,50,22)
text('notes','En orange : Supabase, liens logiques sans clé étrangère entre bases. Les autres relations viennent des FK PostgreSQL.\nLes colonnes created_at / updated_at sont regroupées. Périmètre : tables métier QuickPick, users inclus ; tables techniques exclues.',70,2600,3100,100,22)
ET.indent(mx);target=OUT/'QuickPick-MCD.drawio';ET.ElementTree(mx).write(target,encoding='utf-8',xml_declaration=True)
image.save(OUT/'QuickPick-MCD-une-page.png')
check=ET.parse(target);cells=check.findall('.//mxCell');ids={c.get('id') for c in cells}
assert len(check.findall('.//diagram'))==1
assert all(c.get(k) is None or c.get(k) in ids for c in cells for k in ['source','target','parent'])
assert len(nodes)==15
print(f'Une page, {len(nodes)} blocs, {len(edges)} relations, XML validé : {target}')

from PIL import Image, ImageDraw, ImageFont
from pathlib import Path

OUT = Path(__file__).parent
WINE = '#A60321'; CREAM = '#F2E9D8'; BROWN = '#8C5C32'; INK = '#34271F'
MUTED = '#76685B'; PAPER = '#FFFCF6'; LINE = '#DCCDB8'; GREEN = '#F1E4D6'
FONT = r'C:\Windows\Fonts\arial.ttf'; BOLD = r'C:\Windows\Fonts\arialbd.ttf'
def font(size, bold=False): return ImageFont.truetype(BOLD if bold else FONT, size)
def centered(draw, box, text, f, fill=INK, spacing=5):
    x1,y1,x2,y2=box
    bb=draw.multiline_textbbox((0,0),text,font=f,spacing=spacing,align='center')
    draw.multiline_text(((x1+x2-(bb[2]-bb[0]))/2,(y1+y2-(bb[3]-bb[1]))/2),text,font=f,fill=fill,spacing=spacing,align='center')
def box(draw, xy, text, fill=PAPER, outline=LINE, f=None, radius=18, color=INK):
    draw.rounded_rectangle(xy,radius=radius,fill=fill,outline=outline,width=3)
    centered(draw,xy,text,f or font(21),color)
def arrow(draw, a, b, color=BROWN, width=4):
    import math
    draw.line([a,b],fill=color,width=width)
    ang=math.atan2(b[1]-a[1],b[0]-a[0]); L=14
    p1=(b[0]-L*math.cos(ang-.48),b[1]-L*math.sin(ang-.48)); p2=(b[0]-L*math.cos(ang+.48),b[1]-L*math.sin(ang+.48))
    draw.polygon([b,p1,p2],fill=color)
def title(im, heading, sub):
    d=ImageDraw.Draw(im); d.text((58,35),heading,font=font(34,True),fill=INK); d.text((60,82),sub,font=font(17),fill=MUTED)

# DER: the four entities and the actual foreign keys from the provided SQL.
im=Image.new('RGB',(1600,1000),CREAM); title(im,'StockControl · Diagrama Entidade-Relacionamento','Chaves e relacionamentos conforme o banco stockcontrol.sql')
d=ImageDraw.Draw(im)
entities={
 'USUARIOS':(90,210,690,490,['PK  id  INT AUTO_INCREMENT','nome  VARCHAR(100)','email  VARCHAR(150) UNIQUE','senha  VARCHAR(255)','is_admin  TINYINT(1)']),
 'PRODUTOS':(910,210,1510,490,['PK  id  INT AUTO_INCREMENT','nome  VARCHAR(150)','descricao  TEXT','quantidade  INT · preco  DECIMAL(10,2)','status  TINYINT(1) · imagem  VARCHAR(255)','data_inclusao  TIMESTAMP']),
 'COMENTARIOS':(90,630,690,900,['PK  id  INT AUTO_INCREMENT','FK  produto_id → produtos.id','FK  usuario_id → usuarios.id','texto  TEXT','data_comentario  TIMESTAMP']),
 'MOVIMENTACOES':(910,630,1510,900,['PK  id  INT AUTO_INCREMENT','FK  produto_id → produtos.id','FK  usuario_id → usuarios.id','tipo  ENUM(entrada, saida)','quantidade  INT · data_movimentacao TIMESTAMP','observacoes  TEXT NULL'])}
for name,(x1,y1,x2,y2,fields) in entities.items():
    d.rounded_rectangle((x1,y1,x2,y2),radius=17,fill=PAPER,outline=LINE,width=3)
    d.rounded_rectangle((x1,y1,x2,y1+56),radius=16,fill=WINE,outline=WINE,width=2)
    d.rectangle((x1,y1+35,x2,y1+56),fill=WINE)
    d.text((x1+20,y1+14),name,font=font(22,True),fill='white')
    for i,field in enumerate(fields): d.text((x1+22,y1+76+i*32),field,font=font(17,i==0),fill=INK if i==0 else MUTED)
# Relationships are placed in the central gutter, avoiding entity bodies.
arrow(d,(385,490),(385,630),BROWN); d.text((398,540),'1 : N',font=font(17,True),fill=BROWN)
arrow(d,(1210,490),(1210,630),BROWN); d.text((1223,540),'1 : N',font=font(17,True),fill=BROWN)
d.line([(570,490),(570,550),(850,550),(850,770)],fill=BROWN,width=4); arrow(d,(850,770),(910,770),BROWN)
d.text((700,523),'1 : N  registra',font=font(15,True),fill=BROWN)
d.line([(1030,490),(1030,590),(820,590),(820,770)],fill=BROWN,width=4); arrow(d,(820,770),(690,770),BROWN)
d.text((844,562),'1 : N  recebe',font=font(15,True),fill=BROWN)
im.save(OUT/'der.png')

# Activity diagram for authenticated administrator and product registration.
im=Image.new('RGB',(1500,1750),CREAM); title(im,'StockControl · Atividade: inclusão de produto','Fluxo de autenticação, validação, cadastro e registros relacionados')
d=ImageDraw.Draw(im)
cx=750
d.ellipse((cx-42,135,cx+42,219),fill=GREEN,outline=BROWN,width=3); centered(d,(cx-42,135,cx+42,219),'INÍCIO',font(17,True))
box(d,(500,270,1000,365),'Administrador informa e-mail e senha',fill=PAPER,f=font(21,True))
arrow(d,(cx,219),(cx,270))
# credential diamond
diamond=[(cx,410),(1000,490),(cx,570),(500,490)]; d.polygon(diamond,fill=PAPER,outline=BROWN); d.line(diamond+[diamond[0]],fill=BROWN,width=3); centered(d,(560,445,940,535),'Credenciais\nválidas?',font(21,True))
arrow(d,(cx,365),(cx,410));
# invalid path to left and back
arrow(d,(500,490),(250,490)); d.text((352,460),'Não',font=font(17,True),fill=WINE)
box(d,(70,445,250,535),'Exibir erro\ne voltar ao login',fill='#F8E8E4',outline=WINE,f=font(17))
arrow(d,(160,445),(160,320),WINE); arrow(d,(160,320),(500,320),WINE)
d.text((770,585),'Sim',font=font(17,True),fill=BROWN)
box(d,(500,625,1000,725),'Abrir painel e selecionar\n“Novo produto”',fill=GREEN,outline=BROWN,f=font(20,True)); arrow(d,(cx,570),(cx,625))
box(d,(450,790,1050,920),'Informar nome, descrição, quantidade inicial, preço,\nstatus, imagem e observação inicial (opcional)',f=font(19)); arrow(d,(cx,725),(cx,790))
diamond=[(cx,975),(1015,1045),(cx,1115),(485,1045)]; d.polygon(diamond,fill=PAPER); d.line(diamond+[diamond[0]],fill=BROWN,width=3); centered(d,(555,1008,945,1082),'Todos os campos\nestão válidos?',font(20,True)); arrow(d,(cx,920),(cx,975))
box(d,(80,1000,300,1090),'Indicar campos\nobrigatórios',fill='#F8E8E4',outline=WINE,f=font(17)); arrow(d,(485,1045),(300,1045),WINE); d.text((365,1017),'Não',font=font(17,True),fill=WINE); arrow(d,(190,1000),(190,850)); arrow(d,(190,850),(450,850))
arrow(d,(cx,1115),(cx,1170)); d.text((770,1130),'Sim',font=font(17,True),fill=BROWN)
box(d,(430,1170,1070,1280),'Salvar produto e data de inclusão\nno banco de dados',fill=GREEN,outline=BROWN,f=font(20,True))
arrow(d,(cx,1280),(cx,1330)); box(d,(430,1330,1070,1440),'Registrar entrada inicial no histórico\ncom responsável e quantidade',f=font(19))
arrow(d,(cx,1440),(cx,1490)); box(d,(430,1490,1070,1595),'Redirecionar para a lista\nde produtos ativos',fill=GREEN,outline=BROWN,f=font(19,True))
arrow(d,(cx,1595),(cx,1640)); d.ellipse((cx-42,1640,cx+42,1724),fill=WINE,outline=WINE,width=3); centered(d,(cx-42,1640,cx+42,1724),'FIM',font(17,True),'white')
im.save(OUT/'atividade-inclusao-produto.png')

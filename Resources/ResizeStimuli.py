"""Resize objects and add alpha channel."""
from PIL import Image
import numpy as np
import glob
"""
Note that this replaces all images in place. The original images can
be found other places, and shoudl be recopied if resizing needs to happen again
"""

imNames = []
dirLst = ['guns', 'nonguns']

for dir in dirLst:
    flList = glob.iglob(dir + '/*.png')
    for imName in flList:
        im = Image.open(imName)

        im = im.convert('L')
        #data = np.array(im)
        # add alpha channel on pure white
        #red, green, blue, alpha = data.T
        #whiteAreas = (red == 255) & (green == 255) & (blue == 255)
        #data[][whiteAreas.T] =

        curH = im.height
        curW = im.width
        newH = 380
        newW = int(round(curW * newH / curH))

        im.thumbnail(size=(newW, newH))

        im.save(imName)
